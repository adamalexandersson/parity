/**
 * Host-supplied icon resolution for schema component nodes.
 *
 * Themes register a resolver via window.sprout.registerIconResolver(fn).
 * The resolver receives { name, componentRef, className, props } and returns a
 * React element, or null when it cannot render the icon.
 */

let iconResolver = null;

/**
 * @param {((ctx: {
 *     name: string,
 *     componentRef: string|null,
 *     className?: string,
 *     props?: Record<string, unknown>,
 * }) => unknown)|null} resolver
 */
export function registerIconResolver(resolver) {
    iconResolver = typeof resolver === 'function' ? resolver : null;
}

export function getIconResolver() {
    return iconResolver;
}

/**
 * @param {string} name
 * @param {{ component?: { ref?: string|null, class?: string|null, props?: Record<string, unknown> }|null }} element
 * @returns {unknown|null}
 */
export function resolveIcon(name, element) {
    if (! name || typeof iconResolver !== 'function') {
        return null;
    }

    const comp = element.component ?? {};

    try {
        return iconResolver({
            name,
            componentRef: comp.ref ?? null,
            className: comp.class ?? undefined,
            props: comp.props ?? {},
        }) ?? null;
    } catch (error) {
        if (typeof console !== 'undefined' && console.error) {
            console.error('[Sprout] icon resolver failed:', error);
        }

        return null;
    }
}
