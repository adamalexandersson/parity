export function isProduction(config = null) {
    if (config?.debug === true) {
        return false;
    }

    const root = typeof globalThis !== 'undefined' ? globalThis : {};
    const parity = root.window?.parity ?? root.parity;

    if (parity?.config?.debug === true) {
        return false;
    }

    return typeof process !== 'undefined' && process.env?.NODE_ENV === 'production';
}

export function missingComponentFallback(name, registeredNames = []) {
    const registered = [...registeredNames].sort();
    const message = `[Parity] Unknown component "${name}". Registered: ${registered.length ? registered.join(', ') : '(none)'}`;

    if (! isProduction()) {
        throw new Error(message);
    }

    return function ParityComponentFallback() {
        return null;
    };
}
