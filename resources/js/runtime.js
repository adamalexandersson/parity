import { createComponent, registerComponent } from './render/createComponent.jsx';
import { SchemaRenderer } from './render/schemaRenderer.js';
import { SCHEMA_VERSION } from './schema/version.js';
import { missingComponentFallback } from './support/getComponentHelpers.js';
import { registerIconResolver } from './support/iconResolver.js';

const registry = {};

function getParityConfig() {
    const root = typeof globalThis !== 'undefined' ? globalThis : {};
    const parity = root.window?.parity ?? root.parity;

    return parity?.config ?? {};
}

function bootstrapComponents() {
    const configs = getParityConfig();

    Object.keys(configs).forEach((name) => {
        if (['presets', 'schemaVersion', 'tokens', 'classes', 'debug', 'editor'].includes(name)) {
            return;
        }

        if (typeof configs[name] === 'object' && configs[name]?.schemaVersion) {
            registerComponent(name, null, registry);
        }
    });
}

const config = getParityConfig();

bootstrapComponents();

function getComponent(name) {
    if (registry[name]) {
        return registry[name];
    }

    const componentConfig = getParityConfig()[name];

    if (componentConfig && typeof componentConfig === 'object' && componentConfig.schemaVersion) {
        return registerComponent(name, null, registry);
    }

    return missingComponentFallback(name, Object.keys(registry));
}

const root = typeof globalThis !== 'undefined' ? globalThis : {};
const previous = root.parity && typeof root.parity === 'object' ? root.parity : {};

root.parity = {
    ...previous,
    version: SCHEMA_VERSION,
    config,
    components: registry,
    createComponent,
    registerComponent: (name, component = null) => registerComponent(name, component, registry),
    registerIconResolver,
    getComponent,
    SchemaRenderer,
};

if (typeof window !== 'undefined') {
    window.parity = root.parity;
}

export default root.parity;
