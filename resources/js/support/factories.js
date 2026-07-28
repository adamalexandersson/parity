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

function mergeClassParts(parts) {
    if (parts.length === 0) {
        return '';
    }

    if (resolveClassStrategy() === 'passthrough') {
        const unique = [];

        parts.forEach((part) => {
            String(part).split(/\s+/).forEach((token) => {
                if (token && !unique.includes(token)) {
                    unique.push(token);
                }
            });
        });

        return unique.join(' ');
    }

    return twMerge(...parts);
}

export class ClassFactory {
    constructor() {
        this.parts = [];
        this.merged = '';
        this.dirty = false;
    }

    apply(value) {
        if (!value) {
            return this;
        }

        this.parts.push(String(value));
        this.dirty = true;

        return this;
    }

    get() {
        if (this.dirty) {
            this.merged = mergeClassParts(this.parts);
            this.dirty = false;
        }

        return this.merged;
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
