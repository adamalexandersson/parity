export const VOID_ELEMENTS = [
    'area',
    'base',
    'br',
    'col',
    'embed',
    'hr',
    'img',
    'input',
    'link',
    'meta',
    'param',
    'source',
    'track',
    'wbr',
];

export function isVoidElement(tag) {
    if (!tag) {
        return false;
    }

    return VOID_ELEMENTS.includes(String(tag).toLowerCase());
}
