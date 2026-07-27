export function isDebugMode() {
    const root = typeof globalThis !== 'undefined' ? globalThis : {};
    const parity = root.window?.parity ?? root.parity;

    if (parity?.config?.debug === true) {
        return true;
    }

    return typeof process !== 'undefined' && process.env?.NODE_ENV !== 'production';
}

export function formatSchemaError(error, componentName = null) {
    const path = error?.path ?? null;
    const message = error?.message ?? String(error);
    const parts = [
        componentName ? `[${componentName}]` : null,
        path ? `${path}:` : null,
        message,
    ].filter(Boolean);

    return parts.join(' ');
}

export function schemaErrorPanelProps(error, componentName = null) {
    return {
        'data-parity-error': 'true',
        role: 'alert',
        style: {
            border: '1px solid #b91c1c',
            background: '#fef2f2',
            color: '#7f1d1d',
            padding: '12px',
            fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace',
            fontSize: '12px',
            whiteSpace: 'pre-wrap',
        },
        children: formatSchemaError(error, componentName),
    };
}
