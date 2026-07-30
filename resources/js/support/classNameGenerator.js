/**
 * Generates BEM / kebab / state class tokens from schema naming rules.
 * Mirrors Parity\Support\ClassNameGenerator.
 */

function getParityConfig() {
    const root = typeof globalThis !== 'undefined' ? globalThis : {};
    const parity = root.window?.parity ?? root.parity;

    return parity?.config ?? {};
}

const DEFAULT_BEM = {
    categories: {
        component: 'c-',
        object: 'o-',
        organizer: 'o-',
        module: 'm-',
        utility: 'u-',
    },
    element: '__',
    modifier: '--',
    breakpoint: '@',
};

const DEFAULT_VARIANT = {
    element: '-',
    join: '-',
    format: 'kebab',
};

const DEFAULT_STATE = {
    is: 'is-',
    has: 'has-',
};

export class ClassNameGenerator {
    constructor(bem = null, variant = null, state = null) {
        const config = getParityConfig();

        this.bem = { ...DEFAULT_BEM, ...(bem ?? config.bem ?? {}) };
        this.bem.categories = {
            ...DEFAULT_BEM.categories,
            ...(this.bem.categories ?? {}),
        };
        this.variant = { ...DEFAULT_VARIANT, ...(variant ?? config.variant ?? {}) };
        this.state = { ...DEFAULT_STATE, ...(state ?? config.state ?? {}) };
    }

    resolveBlock(schema) {
        const name = String(schema.block ?? schema.name ?? '');
        const prefix = this.categoryPrefix(schema.category ?? null);

        return `${prefix}${name}`;
    }

    categoryPrefix(category) {
        if (typeof category !== 'string' || category === '') {
            return '';
        }

        let normalized = category.toLowerCase().replace(/s$/, '');

        if (normalized === 'utilitie') {
            normalized = 'utility';
        }

        const aliases = {
            component: 'component',
            object: 'object',
            organizer: 'organizer',
            module: 'module',
            utility: 'utility',
        };
        const key = aliases[normalized] ?? normalized;
        const categories = this.bem.categories ?? {};

        for (const candidate of [key, `${key}s`, category.toLowerCase()]) {
            if (typeof categories[candidate] === 'string') {
                return categories[candidate];
            }
        }

        return '';
    }

    shouldEmitBlock(schema) {
        if (schema.category || schema.block) {
            return true;
        }

        return this.rulesUseNaming(schema.classRules ?? [])
            || this.childrenUseNaming(schema.children ?? {});
    }

    rulesUseNaming(rules) {
        return rules.some((rule) => ['element', 'modifier', 'variant', 'state'].includes(rule.mode));
    }

    childrenUseNaming(children) {
        return Object.values(children).some((child) => {
            if (!child || typeof child !== 'object') {
                return false;
            }

            return this.rulesUseNaming(child.classRules ?? [])
                || this.childrenUseNaming(child.children ?? {});
        });
    }

    namingStyle(rules, hasCategory) {
        let hasModifier = false;
        let hasVariant = false;

        rules.forEach((rule) => {
            if (rule.mode === 'modifier') {
                hasModifier = true;
            }

            if (rule.mode === 'variant') {
                hasVariant = true;
            }
        });

        if (hasModifier) {
            return 'bem';
        }

        if (hasVariant) {
            return 'variant';
        }

        return hasCategory ? 'bem' : 'variant';
    }

    elementClass(block, element, style) {
        const sep = style === 'bem'
            ? (this.bem.element ?? '__')
            : (this.variant.element ?? '-');

        return `${block}${sep}${this.formatSegment(element)}`;
    }

    modifierClass(base, rule, props) {
        const breakpoint = typeof rule.breakpoint === 'string' && rule.breakpoint !== ''
            ? rule.breakpoint
            : null;

        if (Object.prototype.hasOwnProperty.call(rule, 'value') && rule.value !== null && rule.value !== undefined) {
            const key = typeof rule.as === 'string' && rule.as !== '' ? rule.as : 'mod';

            return this.bemModifierToken(base, key, this.formatSegment(String(rule.value)), breakpoint, false);
        }

        const sources = this.normalizeSources(rule.source ?? rule.as ?? null);

        if (sources.length === 0) {
            return null;
        }

        const key = typeof rule.as === 'string' && rule.as !== ''
            ? rule.as
            : (sources.length === 1 ? sources[0] : 'mod');

        if (sources.length === 1) {
            const resolved = this.resolvePropValue(props, sources[0], breakpoint);

            if (this.isOmitValue(resolved)) {
                return null;
            }

            if (typeof resolved === 'boolean') {
                return resolved
                    ? this.bemModifierToken(base, key, null, breakpoint, true)
                    : null;
            }

            return this.bemModifierToken(base, key, this.formatSegment(resolved), breakpoint, false);
        }

        const parts = [];

        for (const source of sources) {
            const resolved = this.resolvePropValue(props, source, breakpoint);

            if (this.isOmitValue(resolved) || typeof resolved === 'boolean') {
                return null;
            }

            parts.push(this.formatSegment(resolved));
        }

        return this.bemModifierToken(base, key, parts.join('-'), breakpoint, false);
    }

    variantClass(base, rule, props) {
        const breakpoint = typeof rule.breakpoint === 'string' && rule.breakpoint !== ''
            ? rule.breakpoint
            : null;
        const join = this.variant.join ?? '-';

        if (Object.prototype.hasOwnProperty.call(rule, 'value') && rule.value !== null && rule.value !== undefined) {
            return this.kebabToken(base, this.formatSegment(String(rule.value)), breakpoint);
        }

        const sources = this.normalizeSources(rule.source ?? null);

        if (sources.length === 0) {
            return null;
        }

        if (sources.length === 1) {
            const key = sources[0];
            const resolved = this.resolvePropValue(props, key, breakpoint);

            if (this.isOmitValue(resolved)) {
                return null;
            }

            if (typeof resolved === 'boolean') {
                return resolved
                    ? this.kebabToken(base, this.formatSegment(key), breakpoint)
                    : null;
            }

            return this.kebabToken(base, this.formatSegment(resolved), breakpoint);
        }

        const parts = [];

        for (const source of sources) {
            const resolved = this.resolvePropValue(props, source, breakpoint);

            if (this.isOmitValue(resolved) || typeof resolved === 'boolean') {
                return null;
            }

            parts.push(this.formatSegment(resolved));
        }

        return this.kebabToken(base, parts.join(join), breakpoint);
    }

    stateClass(rule, props) {
        const kind = rule.state ?? 'is';
        const name = String(rule.stateName ?? rule.source ?? '');
        const source = rule.source ?? name;

        if (name === '' || typeof source !== 'string') {
            return null;
        }

        const resolved = this.lookup(props, source);

        if (!resolved) {
            return null;
        }

        const prefix = this.state[kind] ?? (kind === 'has' ? 'has-' : 'is-');

        return `${prefix}${this.formatSegment(name)}`;
    }

    bemModifierToken(base, key, value, breakpoint, boolean) {
        const mod = this.bem.modifier ?? '--';
        const bpSep = this.bem.breakpoint ?? '@';
        const keySeg = this.formatSegment(key);
        let stem = base;

        if (breakpoint !== null) {
            stem += `${bpSep}${this.formatSegment(breakpoint)}`;
        }

        if (boolean || value === null || value === '') {
            return `${stem}${mod}${keySeg}`;
        }

        return `${stem}${mod}${keySeg}-${value}`;
    }

    kebabToken(base, segment, breakpoint) {
        const join = this.variant.join ?? '-';

        if (breakpoint !== null) {
            return `${base}${join}${this.formatSegment(breakpoint)}${join}${segment}`;
        }

        return `${base}${join}${segment}`;
    }

    normalizeSources(source) {
        if (typeof source === 'string' && source !== '') {
            return [source];
        }

        if (!Array.isArray(source)) {
            return [];
        }

        return source.filter((item) => typeof item === 'string' && item !== '');
    }

    resolvePropValue(props, source, breakpoint) {
        if (breakpoint !== null) {
            const studly = `${source}${breakpoint.charAt(0).toUpperCase()}${breakpoint.slice(1)}`;
            const underscored = `${source}_${breakpoint}`;

            for (const candidate of [studly, underscored]) {
                if (this.hasProp(props, candidate)) {
                    return this.lookup(props, candidate);
                }
            }
        }

        if (this.hasProp(props, source)) {
            return this.lookup(props, source);
        }

        return null;
    }

    hasProp(props, key) {
        if (!key.includes('.')) {
            return Object.prototype.hasOwnProperty.call(props, key);
        }

        return this.pathExists(props, key);
    }

    pathExists(props, key) {
        const parts = key.split('.');
        let value = props;

        for (const part of parts) {
            if (!value || typeof value !== 'object' || !(part in value)) {
                return false;
            }

            value = value[part];
        }

        return true;
    }

    lookup(props, key) {
        if (!key.includes('.')) {
            return props[key] ?? null;
        }

        const parts = key.split('.');
        let value = props;

        for (const part of parts) {
            if (value && typeof value === 'object') {
                value = value[part];
            } else {
                return null;
            }
        }

        return value;
    }

    isOmitValue(value) {
        return value === null || value === undefined || value === false || value === '';
    }

    formatSegment(value) {
        if (typeof value === 'boolean') {
            return value ? 'true' : 'false';
        }

        let string = String(value).trim();
        const format = this.variant.format ?? 'kebab';

        if (format === 'raw') {
            return string;
        }

        string = string.toLowerCase().replace(/[^a-z0-9]+/g, '-');

        return string.replace(/^-+|-+$/g, '');
    }
}
