/**
 * Editor placeholders for schema componentRef nodes (Blade Icons / Heroicons).
 */

export function chevronDownSvgProps(extraClassName = null) {
    return {
        xmlns: 'http://www.w3.org/2000/svg',
        fill: 'none',
        viewBox: '0 0 24 24',
        strokeWidth: 1.5,
        stroke: 'currentColor',
        'aria-hidden': true,
        className: ['size-full', extraClassName].filter(Boolean).join(' ') || undefined,
    };
}

export const CHEVRON_DOWN_PATH = {
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    d: 'm19.5 8.25-7.5 7.5-7.5-7.5',
};
