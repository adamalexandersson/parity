/** @type {readonly string[]} */
export const BOOLEAN_ATTRIBUTES = Object.freeze([
    'disabled',
    'checked',
    'required',
    'readonly',
    'multiple',
    'selected',
    'open',
    'hidden',
    'controls',
    'autoplay',
    'muted',
    'loop',
    'playsinline',
    'defer',
    'async',
    'novalidate',
    'reversed',
    'itemscope',
]);

export function isBooleanAttribute(name) {
    return BOOLEAN_ATTRIBUTES.includes(String(name).toLowerCase());
}
