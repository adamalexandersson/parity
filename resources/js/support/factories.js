import { twMerge } from 'tailwind-merge';

function toReactStyleProperty(property) {
    if (!property.includes('-')) {
        return property;
    }

    return property
        .trim()
        .replace(/^-ms-/, 'ms-')
        .replace(/-([a-z])/g, (_, char) => char.toUpperCase());
}

function resolveClassStrategy() {
    const root = typeof globalThis !== 'undefined' ? globalThis : {};
    const parity = root.window?.parity ?? root.parity;

    return parity?.config?.classes?.strategy ?? 'tailwind';
}

function mergeClasses(existing, value) {
    const next = String(value);

    if (resolveClassStrategy() === 'passthrough') {
        const parts = `${existing} ${next}`.split(/\s+/).filter(Boolean);
        const unique = [];

        parts.forEach((part) => {
            if (!unique.includes(part)) {
                unique.push(part);
            }
        });

        return unique.join(' ');
    }

    return twMerge(existing, next);
}

export class ClassFactory {
    constructor() {
        this.classString = '';
    }

    apply(value) {
        if (!value) {
            return this;
        }

        this.classString = mergeClasses(this.classString, value);

        return this;
    }

    get() {
        return this.classString;
    }
}

export class InlineStyleFactory {
    constructor() {
        this.styles = {};
    }

    add(property, value) {
        if (property && value) {
            this.styles[toReactStyleProperty(property)] = value;
        }

        return this;
    }

    get() {
        return this.styles;
    }
}
