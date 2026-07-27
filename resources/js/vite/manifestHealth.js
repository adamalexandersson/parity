/**
 * Manifest health checks for the Parity Vite plugin.
 */

import fs from 'node:fs';
import path from 'node:path';

/**
 * @param {string} dir
 * @returns {{ file: string, mtimeMs: number } | null}
 */
export function newestPhpFile(dir) {
    if (! fs.existsSync(dir)) {
        return null;
    }

    /** @type {{ file: string, mtimeMs: number } | null} */
    let newest = null;

    const walk = (current) => {
        for (const entry of fs.readdirSync(current, { withFileTypes: true })) {
            const full = path.join(current, entry.name);

            if (entry.isDirectory()) {
                walk(full);
                continue;
            }

            if (! entry.isFile() || ! entry.name.endsWith('.php')) {
                continue;
            }

            const mtimeMs = fs.statSync(full).mtimeMs;

            if (! newest || mtimeMs > newest.mtimeMs) {
                newest = { file: full, mtimeMs };
            }
        }
    };

    walk(dir);

    return newest;
}

/**
 * @param {{
 *   manifestPath: string,
 *   componentsDir: string,
 *   warn: (message: string) => void,
 * }} options
 * @returns {{
 *   ok: boolean,
 *   components: Record<string, unknown>,
 *   reason: 'ok' | 'missing' | 'unparseable' | 'stale',
 * }}
 */
export function checkManifest({ manifestPath, componentsDir, warn }) {
    if (! fs.existsSync(manifestPath)) {
        warn(
            `[Parity] Manifest not found at ${manifestPath}. Run \`wp acorn parity:manifest\` (or \`php artisan parity:manifest\`) then rebuild.`,
        );

        return { ok: false, components: {}, reason: 'missing' };
    }

    let parsed;

    try {
        parsed = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
    } catch (error) {
        const message = error instanceof Error ? error.message : String(error);
        warn(`[Parity] Manifest at ${manifestPath} is unparseable: ${message}`);

        return { ok: false, components: {}, reason: 'unparseable' };
    }

    const components = parsed?.components && typeof parsed.components === 'object'
        ? parsed.components
        : {};

    const manifestMtime = fs.statSync(manifestPath).mtimeMs;
    const newest = newestPhpFile(componentsDir);

    if (newest && newest.mtimeMs > manifestMtime) {
        warn(
            `[Parity] Manifest at ${manifestPath} is older than ${newest.file}. Run \`wp acorn parity:manifest\` (or \`php artisan parity:manifest\`) then rebuild.`,
        );

        return { ok: true, components, reason: 'stale' };
    }

    return { ok: true, components, reason: 'ok' };
}
