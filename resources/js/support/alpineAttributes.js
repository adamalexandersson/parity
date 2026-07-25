/**
 * Alpine.js directive attribute detection for editor suppression.
 */

export function isAlpineAttribute(name) {
    const raw = String(name);

    if (raw.startsWith('x-')) {
        return true;
    }

    // Alpine bind shorthand: :aria-expanded, :class — not HTML xmlns-style.
    if (/^:[a-zA-Z]/.test(raw)) {
        return true;
    }

    return false;
}

export function shouldSuppressAlpine(config = null) {
    const root = typeof globalThis !== 'undefined' ? globalThis : {};
    const sprout = root.window?.sprout ?? root.sprout;
    const editor = config?.editor ?? sprout?.config?.editor ?? {};

    return (editor.alpine ?? 'suppress') !== 'emit';
}
