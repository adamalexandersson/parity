/**
 * Parity editor export helpers.
 * Theme Vite alias: '@parity/runtime' -> this file.
 */

function isProduction() {
    if (typeof window !== 'undefined' && window.parity?.config?.debug === true) {
        return false;
    }

    return typeof process !== 'undefined' && process.env?.NODE_ENV === 'production';
}

export function getComponent(name) {
    if (typeof window !== 'undefined' && window.parity?.getComponent) {
        return window.parity.getComponent(name);
    }

    if (! isProduction()) {
        throw new Error(`[Parity] Runtime unavailable while resolving "${name}". Ensure window.parity is loaded before rendering.`);
    }

    return function ParityComponentFallback() {
        return null;
    };
}

export function createExport(slug) {
    const ComponentExport = function ParityExport(props) {
        const Component = getComponent(slug);

        if (typeof window !== 'undefined' && window.wp?.element?.createElement) {
            return window.wp.element.createElement(Component, props);
        }

        return Component(props);
    };

    ComponentExport.displayName = `Parity(${slug})`;

    return ComponentExport;
}
