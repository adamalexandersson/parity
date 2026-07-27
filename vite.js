/**
 * First-party Vite plugin for Parity.
 *
 * Usage in a consuming project:
 *
 *   import parity from './vendor/adamalexandersson/parity/vite.js';
 *   export default defineConfig({ plugins: [parity()] });
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { buildModule, emptyModule, slugToExportMap } from './resources/js/vite/module.js';
import { buildTypes } from './resources/js/vite/types.js';
import { checkManifest } from './resources/js/vite/manifestHealth.js';

const VIRTUAL_ID = 'virtual:parity/components';
const RESOLVED_VIRTUAL_ID = `\0${VIRTUAL_ID}`;

const packageRoot = path.dirname(fileURLToPath(import.meta.url));
const runtimePath = path.join(packageRoot, 'resources/js/editor/runtime.js');
const canvasPath = path.join(packageRoot, 'resources/js/editor/canvas.js');

/**
 * @param {{
 *   manifest?: string,
 *   types?: string | false,
 *   componentsDir?: string,
 * }} [options]
 * @returns {import('vite').Plugin}
 */
export default function parity(options = {}) {
    const {
        manifest = 'resources/js/parity/manifest.json',
        types = 'resources/js/parity/components.d.ts',
        componentsDir = 'app/View/Components',
    } = options;

    /** @type {string} */
    let root = process.cwd();
    /** @type {Record<string, unknown>} */
    let components = {};
    /** @type {((message: string) => void) | null} */
    let warnFn = null;

    const resolveFromRoot = (relative) => path.resolve(root, relative);

    const warn = (message) => {
        if (warnFn) {
            warnFn(message);
            return;
        }

        console.warn(message);
    };

    const refresh = () => {
        const result = checkManifest({
            manifestPath: resolveFromRoot(manifest),
            componentsDir: resolveFromRoot(componentsDir),
            warn,
        });

        components = result.components;

        if (types !== false) {
            writeTypes(resolveFromRoot(types), components);
        }

        return result;
    };

    return {
        name: 'parity',

        configResolved(config) {
            root = config.root;
            warnFn = (message) => config.logger.warn(message);
        },

        resolveId(id) {
            if (id === '@parity/runtime') {
                return runtimePath;
            }

            if (id === '@parity/canvas') {
                return canvasPath;
            }

            if (id === '@parity/components' || id === VIRTUAL_ID) {
                return RESOLVED_VIRTUAL_ID;
            }

            return null;
        },

        load(id) {
            if (id !== RESOLVED_VIRTUAL_ID) {
                return null;
            }

            if (Object.keys(components).length === 0) {
                refresh();
            }

            if (Object.keys(components).length === 0) {
                return emptyModule();
            }

            return buildModule(slugToExportMap(components));
        },

        buildStart() {
            refresh();
        },

        configureServer(server) {
            const manifestPath = resolveFromRoot(manifest);

            server.watcher.add(manifestPath);

            const onChange = (file) => {
                if (path.resolve(file) !== path.resolve(manifestPath)) {
                    return;
                }

                refresh();

                const module = server.moduleGraph.getModuleById(RESOLVED_VIRTUAL_ID);

                if (module) {
                    server.moduleGraph.invalidateModule(module);
                }

                server.ws.send({ type: 'full-reload' });
            };

            server.watcher.on('change', onChange);
            server.watcher.on('add', onChange);
        },
    };
}

/**
 * @param {string} typesPath
 * @param {Record<string, unknown>} components
 */
function writeTypes(typesPath, components) {
    fs.mkdirSync(path.dirname(typesPath), { recursive: true });
    fs.writeFileSync(typesPath, buildTypes(components));
}

export {
    VIRTUAL_ID,
    RESOLVED_VIRTUAL_ID,
    buildModule,
    buildTypes,
    emptyModule,
    slugToExportMap,
    checkManifest,
};
