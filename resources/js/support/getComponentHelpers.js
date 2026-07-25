export function isProduction(config = null) {
    if (config?.debug === true) {
        return false;
    }

    const root = typeof globalThis !== 'undefined' ? globalThis : {};
    const sprout = root.window?.sprout ?? root.sprout;

    if (sprout?.config?.debug === true) {
        return false;
    }

    return typeof process !== 'undefined' && process.env?.NODE_ENV === 'production';
}

export function missingComponentFallback(name, registeredNames = []) {
    const registered = [...registeredNames].sort();
    const message = `[Sprout] Unknown component "${name}". Registered: ${registered.length ? registered.join(', ') : '(none)'}`;

    if (! isProduction()) {
        throw new Error(message);
    }

    return function SproutComponentFallback() {
        return null;
    };
}
