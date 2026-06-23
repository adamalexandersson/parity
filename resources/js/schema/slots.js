/**
 * Collect declared named slot identifiers from a v1.0 component schema.
 *
 * @param {Record<string, unknown>} schema
 * @returns {string[]}
 */
export function collectNamedSlots(schema) {
    const names = new Set();

    walk(schema, names);

    return [...names];
}

/**
 * @param {Record<string, unknown>} schema
 * @param {Set<string>} names
 */
function walk(schema, names) {
    const slot = schema?.slot;

    if (slot && typeof slot === 'object' && slot.name && !slot.default) {
        names.add(String(slot.name));
    }

    Object.values(schema?.children ?? {}).forEach((child) => {
        if (child && typeof child === 'object') {
            walk(child, names);
        }
    });
}

/**
 * @param {Record<string, unknown>} config
 * @returns {string[]}
 */
export function resolveNamedSlotNames(config) {
    if (Array.isArray(config?.namedSlots) && config.namedSlots.length > 0) {
        return config.namedSlots;
    }

    return collectNamedSlots(config);
}
