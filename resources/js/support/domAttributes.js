import { isBooleanAttribute } from './booleanAttributes.js';
import { mapAttributes } from './reactAttributeMap.js';
import { isAlpineAttribute, shouldSuppressAlpine } from './alpineAttributes.js';

export function normalizeDomAttributes(attributes = {}, {
    tag = null,
    svgParent = false,
    suppressAlpine = null,
} = {}) {
    const omitAlpine = suppressAlpine ?? shouldSuppressAlpine();
    const prepared = {};

    Object.entries(attributes).forEach(([name, value]) => {
        if (omitAlpine && isAlpineAttribute(name)) {
            return;
        }

        if (isBooleanAttribute(name)) {
            if (value) {
                prepared[name] = true;
            }

            return;
        }

        if (value === null || value === undefined || value === false) {
            return;
        }

        prepared[name] = value;
    });

    return mapAttributes(prepared, { tag, svgParent });
}
