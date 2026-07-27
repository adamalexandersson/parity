export function fingerprint(input) {
    let hash = 5381;

    for (let i = 0; i < input.length; i++) {
        hash = (((hash << 5) + hash) + input.charCodeAt(i)) >>> 0;
    }

    return hash.toString(16).padStart(8, '0');
}

export function resolveInstanceKey(componentName, props = {}) {
    for (const key of ['instanceId', 'id']) {
        const value = props[key];

        if (value !== null && value !== undefined && value !== '' && (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean')) {
            return String(value);
        }
    }

    const scalars = {};

    Object.keys(props).sort().forEach((key) => {
        const value = props[key];

        if (value === null || ['string', 'number', 'boolean'].includes(typeof value)) {
            scalars[key] = value;
        }
    });

    return fingerprint(`${componentName}|${JSON.stringify(scalars)}`);
}

export class InstanceIds {
    constructor(componentName, props = {}) {
        this.ids = {};
        this.declared = {};
        this.instanceKey = resolveInstanceKey(componentName, props);
    }

    declare(name) {
        this.declared[name] = true;

        return this.get(name);
    }

    has(name) {
        return Boolean(this.declared[name] || this.ids[name]);
    }

    get(name) {
        if (! this.ids[name]) {
            this.ids[name] = `parity-${this.instanceKey}-${name}`;
        }

        return this.ids[name];
    }
}

/**
 * True when a string contains `{name}` placeholders (including escaped `{{name}}`).
 */
export function shouldInterpolateIds(value) {
    return typeof value === 'string' && /\{[a-zA-Z_][\w-]*\}/.test(value);
}

/**
 * Replace `{name}` placeholders with generated instance IDs.
 * Escape: `{{name}}` becomes a literal `{name}`.
 */
export function interpolateIds(value, ids, { debug = false, component = null } = {}) {
    const escapes = [];
    const ESC = '\u0000';

    let result = String(value).replace(/\{\{([a-zA-Z_][\w-]*)\}\}/g, (_, name) => {
        const index = escapes.length;
        escapes.push(`{${name}}`);

        return `${ESC}${index}${ESC}`;
    });

    result = result.replace(/\{([a-zA-Z_][\w-]*)\}/g, (match, name) => {
        if (! ids.has(name)) {
            if (debug) {
                const error = new Error(
                    `Unknown id placeholder "${name}" in "${value}". Declare it with uniqueId()/idRef() first.`
                );
                error.component = component;
                throw error;
            }

            return match;
        }

        return ids.get(name);
    });

    return result.replace(new RegExp(`${ESC}(\\d+)${ESC}`, 'g'), (_, index) => escapes[Number(index)]);
}
