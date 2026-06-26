import { ClassFactory, InlineStyleFactory } from '../support/factories.js';
import { assertSchemaVersion } from '../schema/version.js';

function getSproutConfig() {
    return window.sprout?.config ?? {};
}

export class SchemaRenderer {
    constructor(componentName, props = {}, config = null) {
        this.componentName = componentName;
        this.config = config ?? getSproutConfig()[componentName] ?? {};
        this.props = props;

        assertSchemaVersion(this.config);
    }

    renderComponentAttributes() {
        const classes = new ClassFactory();
        this.applyClassRules(this.config.classRules ?? [], classes);
        this.applyMatches(this.config.matches ?? [], classes);

        if (this.props.className) {
            classes.apply(this.props.className);
        }

        const styles = new InlineStyleFactory();
        this.applyStyles(this.config.styles ?? [], styles);

        const attributes = {
            className: classes.get(),
        };

        const styleObject = styles.get();

        if (Object.keys(styleObject).length > 0) {
            attributes.style = styleObject;
        }

        if (this.componentName) {
            attributes['data-component'] = this.componentName;
        }

        this.applyAttributes(this.config.attributes ?? [], attributes);

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
        this.applyClassRules(schema.classRules ?? [], classes);
        this.applyMatches(schema.matches ?? [], classes);

        const attributes = {};

        if (classes.get()) {
            attributes.className = classes.get();
        }

        this.applyAttributes(schema.attributes ?? [], attributes);

        const styles = new InlineStyleFactory();
        this.applyStyles(schema.styles ?? [], styles);

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
            componentRef: schema.componentRef ?? null,
            componentProps: schema.componentProps ?? {},
            componentMapping: schema.componentMapping ?? null,
            componentMappingKey: schema.componentMappingKey ?? null,
            componentClass: schema.componentClass ?? null,
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

            if (rule.mode === 'token') {
                const tokenClasses = getSproutConfig().tokens?.[rule.tokenGroup]?.[rule.token];

                if (tokenClasses) {
                    classes.apply(tokenClasses);
                }

                return;
            }

            if (rule.classes) {
                classes.apply(rule.classes);
            }
        });
    }

    applyMatches(matches, classes) {
        matches.forEach((match) => {
            if (match.common) {
                this.applyCommonMatch(match, classes);
                return;
            }

            if (!this.evaluateCondition(match.condition ?? null)) {
                return;
            }

            const values = (match.props ?? []).map((prop) => this.lookupValue(prop));
            const outcomes = this.findMatchCase(match.cases ?? [], values) ?? (match.default ?? []);
            this.applyOutcomes(outcomes, classes);
        });
    }

    applyCommonMatch(match, classes) {
        if (!this.evaluateCondition(match.condition ?? null)) {
            return;
        }

        const prop = match.props?.[0] ?? match.common;
        const value = this.normalizeLookupValue(this.lookupValue(prop));
        const map = getSproutConfig().common?.[match.common] ?? {};

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

        this.applyCommonMapEntry(match.common, value, classes);
    }

    applyCommonMapEntry(commonKey, normalizedValue, classes) {
        const common = getSproutConfig().common ?? {};
        const map = common[commonKey] ?? {};

        if (map[normalizedValue]) {
            classes.apply(map[normalizedValue]);
        }

        const nestedMap = common[`${commonKey}Nested`] ?? {};

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

    applyOutcomes(outcomes, classes) {
        outcomes.forEach((outcome) => {
            if (outcome.type === 'classes') {
                classes.apply(outcome.value ?? '');
            }
        });
    }

    applyAttributes(definitions, target) {
        definitions.forEach((definition) => {
            if (!this.evaluateCondition(definition.condition ?? null)) {
                return;
            }

            if (!definition.source) {
                if (definition.value !== null && definition.value !== undefined && definition.value !== false) {
                    target[definition.name] = definition.value;
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

            target[definition.name] = this.cast(definition.cast ?? 'string', value);
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

        const prop = condition.prop;
        const operator = condition.operator ?? 'truthy';
        const expected = condition.value;
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
            default:
                return false;
        }
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
