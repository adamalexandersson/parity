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

export class ClassFactory {
    constructor() {
        this.classString = '';
    }

    apply(value) {
        if (!value) {
            return this;
        }

        this.classString = twMerge(this.classString, String(value));

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
