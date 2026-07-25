/**
 * Sprout editor export helpers.
 * Theme Vite alias: '@sprout/runtime' -> this file.
 */

function isProduction() {
    if (typeof window !== 'undefined' && window.sprout?.config?.debug === true) {
        return false;
    }

    return typeof process !== 'undefined' && process.env?.NODE_ENV === 'production';
}

export function getComponent(name) {
    if (typeof window !== 'undefined' && window.sprout?.getComponent) {
        return window.sprout.getComponent(name);
    }

    if (! isProduction()) {
        throw new Error(`[Sprout] Runtime unavailable while resolving "${name}". Ensure window.sprout is loaded before rendering.`);
    }

    return function SproutComponentFallback() {
        return null;
    };
}

export function createExport(slug) {
    const ComponentExport = function SproutExport(props) {
        const Component = getComponent(slug);

        if (typeof window !== 'undefined' && window.wp?.element?.createElement) {
            return window.wp.element.createElement(Component, props);
        }

        return Component(props);
    };

    ComponentExport.displayName = `Sprout(${slug})`;

    return ComponentExport;
}
