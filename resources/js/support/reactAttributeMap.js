const REACT_ATTRIBUTE_MAP = {
    class: 'className',
    for: 'htmlFor',
    tabindex: 'tabIndex',
    readonly: 'readOnly',
    maxlength: 'maxLength',
    minlength: 'minLength',
    novalidate: 'noValidate',
    playsinline: 'playsInline',
    allowfullscreen: 'allowFullScreen',
    autocomplete: 'autoComplete',
    autofocus: 'autoFocus',
    autoplay: 'autoPlay',
    colspan: 'colSpan',
    rowspan: 'rowSpan',
    crossorigin: 'crossOrigin',
    datetime: 'dateTime',
    formaction: 'formAction',
    formenctype: 'formEncType',
    formmethod: 'formMethod',
    formnovalidate: 'formNoValidate',
    formtarget: 'formTarget',
    frameborder: 'frameBorder',
    marginheight: 'marginHeight',
    marginwidth: 'marginWidth',
    maxlength: 'maxLength',
    radiogroup: 'radioGroup',
    spellcheck: 'spellCheck',
    srcdoc: 'srcDoc',
    srcset: 'srcSet',
    usemap: 'useMap',
};

const SVG_ATTRIBUTE_MAP = {
    viewbox: 'viewBox',
    strokewidth: 'strokeWidth',
    clippath: 'clipPath',
    fillopacity: 'fillOpacity',
    strokeopacity: 'strokeOpacity',
    strokelinedcap: 'strokeLinecap',
    strokelinedjoin: 'strokeLinejoin',
    strokedasharray: 'strokeDasharray',
    fontsize: 'fontSize',
    fontfamily: 'fontFamily',
    gradientunits: 'gradientUnits',
    gradienttransform: 'gradientTransform',
    patternunits: 'patternUnits',
    preserveaspectratio: 'preserveAspectRatio',
    markerend: 'markerEnd',
    markerstart: 'markerStart',
    markermid: 'markerMid',
};

const SVG_TAGS = new Set([
    'svg',
    'path',
    'g',
    'circle',
    'rect',
    'line',
    'polyline',
    'polygon',
    'ellipse',
    'text',
    'tspan',
    'defs',
    'clippath',
    'mask',
    'use',
    'symbol',
    'lineargradient',
    'radialgradient',
    'stop',
    'pattern',
    'image',
    'foreignobject',
]);

export function isSvgTag(tag) {
    return SVG_TAGS.has(String(tag ?? '').toLowerCase());
}

export function mapAttributeName(name, { svg = false } = {}) {
    const raw = String(name);
    const lower = raw.toLowerCase();

    if (
        lower.startsWith('data-')
        || lower.startsWith('aria-')
        || lower.startsWith('item')
        || lower.startsWith('x-')
        || /^:[a-z]/.test(raw)
    ) {
        return raw;
    }

    if (svg && SVG_ATTRIBUTE_MAP[lower]) {
        return SVG_ATTRIBUTE_MAP[lower];
    }

    return REACT_ATTRIBUTE_MAP[lower] ?? raw;
}

export function mapAttributes(attributes = {}, { tag = null, svgParent = false } = {}) {
    const svg = svgParent || isSvgTag(tag);
    const mapped = {};

    Object.entries(attributes).forEach(([name, value]) => {
        mapped[mapAttributeName(name, { svg })] = value;
    });

    if (svg && String(tag).toLowerCase() === 'svg' && mapped.xmlns === undefined) {
        mapped.xmlns = 'http://www.w3.org/2000/svg';
    }

    return mapped;
}
