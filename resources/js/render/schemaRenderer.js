import { ClassFactory, InlineStyleFactory } from '../support/factories.js';
import { InstanceIds, interpolateIds, shouldInterpolateIds } from '../support/instanceIds.js';
import { assertSchemaVersion } from '../schema/version.js';

function getSproutConfig() {
    const root = typeof globalThis !== 'undefined' ? globalThis : {};
    const sprout = root.window?.sprout ?? root.sprout;

    return sprout?.config ?? {};
}

export class SchemaRenderer {
    constructor(componentName, props = {}, config = null) {
        this.componentName = componentName;
        this.config = config ?? getSproutConfig()[componentName] ?? {};
        this.props = props;
        this.instanceIds = new InstanceIds(componentName, props);
        this.predeclareIds(this.config);

        assertSchemaVersion(this.config);
    }

    renderComponentAttributes() {
        const classes = new ClassFactory();
        const styles = new InlineStyleFactory();
        const attributes = {};

        this.applyClassRules(this.config.classRules ?? [], classes);
        this.applyMatches(this.config.matches ?? [], classes, attributes, styles);

        if (this.props.className) {
            classes.apply(this.props.className);
        }

        this.applyStyles(this.config.styles ?? [], styles);
        this.applyAttributes(this.config.attributes ?? [], attributes);

        attributes.className = classes.get();

        const styleObject = styles.get();

        if (Object.keys(styleObject).length > 0) {
            attributes.style = styleObject;
        }

        if (this.componentName) {
            attributes['data-component'] = this.componentName;
        }

        return attributes;
    }

    renderStructure() {
        const built = {};

        Object.entries(this.config.children ?? {}).forEach(([key, child]) => {
            if (this.shouldRenderNode(child)) {
                built[key] = this.renderNode(child, key, null);
            }
        });

        return built;
    }

    renderNode(schema, key, parentPath = null) {
        const path = parentPath ? `${parentPath}.${key}` : key;
        const classes = new ClassFactory();
        const styles = new InlineStyleFactory();
        const attributes = {};

        this.applyClassRules(schema.classRules ?? [], classes);
        this.applyMatches(schema.matches ?? [], classes, attributes, styles);
        this.applyAttributes(schema.attributes ?? [], attributes);
        this.applyStyles(schema.styles ?? [], styles);

        if (classes.get()) {
            attributes.className = classes.get();
        }

        if (Object.keys(styles.get()).length > 0) {
            attributes.style = styles.get();
        }

        const children = {};

        Object.entries(schema.children ?? {}).forEach(([childKey, childSchema]) => {
            if (this.shouldRenderNode(childSchema)) {
                children[childKey] = this.renderNode(childSchema, childKey, path);
            }
        });

        return {
            key,
            path,
            tag: schema.fragment ? null : (schema.tag ?? 'div'),
            fragment: Boolean(schema.fragment),
            attributes,
            slot: schema.slot ?? null,
            richText: schema.richText ?? null,
            component: schema.component ?? null,
            children,
        };
    }

    shouldRenderNode(schema) {
        if (schema.visible && !this.evaluateCondition(schema.visible)) {
            return false;
        }

        if (schema.hidden && this.evaluateCondition(schema.hidden)) {
            return false;
        }

        return true;
    }

    applyClassRules(rules, classes) {
        rules.forEach((rule) => {
            if (!this.evaluateCondition(rule.condition ?? null)) {
                return;
            }

            const mode = rule.mode ?? null;

            if (mode === 'token') {
                const tokenClasses = getSproutConfig().tokens?.[rule.tokenGroup]?.[rule.token];

                if (tokenClasses) {
                    classes.apply(tokenClasses);
                }

                return;
            }

            // Reserved / unknown modes (e.g. element, modifier) are no-ops until implemented.
            if (mode !== null) {
                return;
            }

            if (rule.classes) {
                classes.apply(rule.classes);
            }
        });
    }

    applyMatches(matches, classes, attributes = {}, styles = null) {
        matches.forEach((match) => {
            if (match.preset) {
                this.applyPresetMatch(match, classes);
                return;
            }

            if (!this.evaluateCondition(match.condition ?? null)) {
                return;
            }

            const values = (match.props ?? []).map((prop) => this.lookupValue(prop));
            const outcomes = this.findMatchCase(match.cases ?? [], values) ?? (match.default ?? []);
            this.applyOutcomes(outcomes, classes, attributes, styles);
        });
    }

    applyPresetMatch(match, classes) {
        if (!this.evaluateCondition(match.condition ?? null)) {
            return;
        }

        const prop = match.props?.[0] ?? match.preset;
        const value = this.normalizeLookupValue(this.lookupValue(prop));
        const map = getSproutConfig().presets?.[match.preset] ?? {};

        if (map.base && map.responsive) {
            if (map.base[value]) {
                classes.apply(map.base[value]);
            }

            Object.entries(map.responsive).forEach(([responsiveProp, breakpoint]) => {
                const responsiveValue = this.normalizeLookupValue(this.lookupValue(responsiveProp));

                if (map[breakpoint]?.[responsiveValue]) {
                    classes.apply(map[breakpoint][responsiveValue]);
                }
            });

            return;
        }

        this.applyPresetMapEntry(match.preset, value, classes);
    }

    applyPresetMapEntry(presetKey, normalizedValue, classes) {
        const presets = getSproutConfig().presets ?? {};
        const map = presets[presetKey] ?? {};

        if (map[normalizedValue]) {
            classes.apply(map[normalizedValue]);
        }

        const nestedMap = presets[`${presetKey}Nested`] ?? {};

        if (nestedMap[normalizedValue]) {
            classes.apply(nestedMap[normalizedValue]);
        }
    }

    findMatchCase(cases, values) {
        for (const caseItem of cases) {
            const caseValues = caseItem.values ?? [];
            let matched = true;

            values.forEach((value, index) => {
                if (!this.matchCaseValue(this.normalizeLookupValue(value), caseValues[index] ?? '')) {
                    matched = false;
                }
            });

            if (matched) {
                return caseItem.outcomes ?? [];
            }
        }

        return null;
    }

    matchCaseValue(normalized, caseValue) {
        const caseLabel = String(caseValue ?? '');

        if (normalized === caseLabel) {
            return true;
        }

        if (caseLabel === 'true' && (normalized === 'true' || normalized === '1')) {
            return true;
        }

        if (caseLabel === 'false' && (normalized === 'false' || normalized === '0' || normalized === '')) {
            return true;
        }

        if (normalized === '' && caseLabel === 'default') {
            return true;
        }

        return false;
    }

    applyOutcomes(outcomes, classes, attributes = {}, styles = null) {
        outcomes.forEach((outcome) => {
            if (outcome.type === 'classes') {
                classes.apply(outcome.value ?? '');
                return;
            }

            if (outcome.type === 'attr' && outcome.name) {
                const value = outcome.value;

                if (value === null || value === undefined || value === false || value === '') {
                    return;
                }

                attributes[outcome.name] = this.resolveAttributeValue(value);
                return;
            }

            if (outcome.type === 'style' && outcome.property && styles) {
                const value = outcome.value;

                if (value === null || value === undefined || value === false || value === '') {
                    return;
                }

                styles.add(outcome.property, String(value));
                return;
            }

            if (outcome.type && !['classes', 'attr', 'style'].includes(outcome.type)) {
                this.failLoud(`Unknown match outcome type "${outcome.type}".`);
            }
        });
    }

    failLoud(message, path = null) {
        const debug = getSproutConfig().debug === true
            || (typeof process !== 'undefined' && process.env?.NODE_ENV !== 'production');

        if (! debug) {
            return;
        }

        const error = new Error(message);
        error.path = path;
        throw error;
    }

    predeclareIds(schema) {
        (schema.attributes ?? []).forEach((definition) => {
            if (definition.uniqueId) {
                this.instanceIds.declare(String(definition.uniqueId));
            }

            if (definition.idRef) {
                this.instanceIds.declare(String(definition.idRef));
            }
        });

        Object.values(schema.children ?? {}).forEach((child) => {
            if (child && typeof child === 'object') {
                this.predeclareIds(child);
            }
        });
    }

    resolveAttributeValue(value) {
        if (! shouldInterpolateIds(value)) {
            return value;
        }

        const debug = getSproutConfig().debug === true
            || (typeof process !== 'undefined' && process.env?.NODE_ENV !== 'production');

        return interpolateIds(value, this.instanceIds, {
            debug,
            component: this.componentName,
        });
    }

    applyAttributes(definitions, target) {
        definitions.forEach((definition) => {
            if (!this.evaluateCondition(definition.condition ?? null)) {
                return;
            }

            if (definition.uniqueId) {
                target[definition.name ?? 'id'] = this.instanceIds.declare(String(definition.uniqueId));
                return;
            }

            if (definition.idRef) {
                target[definition.name] = this.instanceIds.declare(String(definition.idRef));
                return;
            }

            if (!definition.source) {
                if (definition.value !== null && definition.value !== undefined && definition.value !== false) {
                    target[definition.name] = this.resolveAttributeValue(definition.value);
                }

                return;
            }

            let value = this.lookupValue(definition.source);

            if ((value === null || value === undefined || value === false || value === '') && definition.default !== undefined) {
                value = definition.default;
            }

            if (value === null || value === undefined || value === false || value === '') {
                return;
            }

            target[definition.name] = this.resolveAttributeValue(
                this.cast(definition.cast ?? 'string', value),
            );
        });
    }

    applyStyles(definitions, styles) {
        definitions.forEach((definition) => {
            if (!this.evaluateCondition(definition.condition ?? null)) {
                return;
            }

            let value = this.lookupValue(definition.source ?? '');

            if ((value === null || value === undefined || value === false || value === '') && definition.default !== undefined) {
                value = definition.default;
            }

            if (value === null || value === undefined || value === false || value === '') {
                return;
            }

            let resolved = this.cast(definition.cast ?? 'string', value);

            if (definition.cssUrl) {
                resolved = this.cast('cssUrl', resolved);
            }

            styles.add(definition.property, String(resolved));
        });
    }

    cast(name, value) {
        switch (name) {
            case 'boolean':
                return Boolean(value);
            case 'integer':
                return parseInt(value, 10);
            case 'url':
                return String(value);
            case 'cssUrl':
                return `url(${String(value).replace(/^url\(|\)$/gi, '')})`;
            default:
                return String(value);
        }
    }

    evaluateCondition(condition) {
        if (condition === null) {
            return true;
        }

        if (typeof condition === 'string') {
            const value = this.lookupValue(condition);
            return value !== null && value !== undefined && value !== false && value !== '';
        }

        const operator = condition.operator ?? 'truthy';

        if (operator === 'any') {
            return (condition.conditions ?? []).some((sub) => this.evaluateCondition(sub));
        }

        if (operator === 'all') {
            return (condition.conditions ?? []).every((sub) => this.evaluateCondition(sub));
        }

        const prop = condition.prop;
        const expected = condition.value;

        if (!prop) {
            return false;
        }

        const value = this.lookupValue(prop);

        switch (operator) {
            case 'truthy':
                return value !== null && value !== undefined && value !== false && value !== '';
            case 'falsy':
                return value === null || value === undefined || value === false || value === '';
            case 'equals':
            case '==':
                return value === expected;
            case 'notEquals':
            case '!=':
                return value !== expected;
            case 'in':
                return Array.isArray(expected) && expected.includes(value);
            case 'notIn':
                return Array.isArray(expected) && !expected.includes(value);
            case 'gt':
            case 'gte':
            case 'lt':
            case 'lte':
                return this.compareNumeric(operator, value, expected);
            case 'contains':
                return this.evaluateContains(value, expected);
            case 'empty':
                return this.isEmptyValue(value);
            case 'notEmpty':
                return !this.isEmptyValue(value);
            default:
                return false;
        }
    }

    compareNumeric(operator, value, expected) {
        if (value === null || value === undefined || expected === null || expected === undefined) {
            return false;
        }

        const left = Number(value);
        const right = Number(expected);

        if (!Number.isFinite(left) || !Number.isFinite(right)) {
            return false;
        }

        switch (operator) {
            case 'gt':
                return left > right;
            case 'gte':
                return left >= right;
            case 'lt':
                return left < right;
            case 'lte':
                return left <= right;
            default:
                return false;
        }
    }

    evaluateContains(value, expected) {
        if (Array.isArray(value)) {
            return value.includes(expected);
        }

        if (typeof value === 'string' && (typeof expected === 'string' || typeof expected === 'number')) {
            return value.includes(String(expected));
        }

        return false;
    }

    isEmptyValue(value) {
        return value === null
            || value === undefined
            || value === false
            || value === ''
            || (Array.isArray(value) && value.length === 0)
            || (typeof value === 'string' && value.trim() === '');
    }

    lookupValue(key) {
        const parts = String(key).split('.');
        let value = this.props;

        for (const part of parts) {
            if (value && typeof value === 'object') {
                value = value[part];
            } else {
                return null;
            }
        }

        return value;
    }

    normalizeLookupValue(value) {
        if (typeof value === 'boolean') {
            return value ? 'true' : 'false';
        }

        if (typeof value === 'number') {
            return String(value);
        }

        if (typeof value === 'string') {
            const trimmed = value.trim();

            return trimmed === '' ? '' : trimmed;
        }

        if (value === null || value === undefined) {
            return '';
        }

        return String(value);
    }
}
