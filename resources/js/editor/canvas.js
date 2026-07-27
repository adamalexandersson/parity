/**
 * Gutenberg canvas helpers for WordPress consumers.
 *
 * Free of @wordpress/* imports — the iframe canvas does not provide the `wp`
 * global that Vite's WordPress plugin expects.
 */

/**
 * Inside the Gutenberg iframe, copy window.parent.parity.config onto
 * window.parity when the package's iframe injection has not already run.
 */
export function bridgeCanvasConfig() {
    if (typeof window === 'undefined') {
        return;
    }

    if (! window.parent || window.parent === window) {
        return;
    }

    if (window.parity?.config) {
        return;
    }

    if (! window.parent.parity?.config) {
        return;
    }

    window.parity = window.parity || {};
    window.parity.config = window.parent.parity.config;
}

/**
 * Opt-in Alpine boot helper for the editor canvas.
 *
 * The Alpine instance is injected by the caller so the package takes no
 * alpinejs dependency and stays WordPress-free for Laravel consumers.
 *
 * @param {any} Alpine
 * @param {{
 *   data?: Record<string, unknown>,
 *   plugins?: unknown[],
 * }} [options]
 */
export function bootAlpine(Alpine, options = {}) {
    if (! Alpine || typeof Alpine.start !== 'function') {
        return;
    }

    if (typeof window !== 'undefined' && window.__parityAlpineStarted) {
        return;
    }

    const data = options.data ?? {};
    const plugins = options.plugins ?? [];

    for (const [name, factory] of Object.entries(data)) {
        Alpine.data(name, factory);
    }

    for (const plugin of plugins) {
        Alpine.plugin(plugin);
    }

    if (typeof window !== 'undefined') {
        window.Alpine = Alpine;
        window.__parityAlpineStarted = true;
    }

    Alpine.start();
}
