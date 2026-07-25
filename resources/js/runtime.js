import { createComponent, registerComponent } from './render/createComponent.jsx';
import { SchemaRenderer } from './render/schemaRenderer.js';
import { SCHEMA_VERSION } from './schema/version.js';
import { missingComponentFallback } from './support/getComponentHelpers.js';

const registry = {};

function getSproutConfig() {
    const root = typeof globalThis !== 'undefined' ? globalThis : {};
    const sprout = root.window?.sprout ?? root.sprout;

    return sprout?.config ?? {};
}

function bootstrapComponents() {
    const configs = getSproutConfig();

    Object.keys(configs).forEach((name) => {
        if (['common', 'schemaVersion', 'tokens', 'classes', 'debug'].includes(name)) {
            return;
        }

        if (typeof configs[name] === 'object' && configs[name]?.schemaVersion) {
            registerComponent(name, registry);
        }
    });
}

const config = getSproutConfig();

bootstrapComponents();

function getComponent(name) {
    if (registry[name]) {
        return registry[name];
    }

    const componentConfig = getSproutConfig()[name];

    if (componentConfig && typeof componentConfig === 'object' && componentConfig.schemaVersion) {
        return registerComponent(name, registry);
    }

    return missingComponentFallback(name, Object.keys(registry));
}

const root = typeof globalThis !== 'undefined' ? globalThis : {};
const previous = root.sprout && typeof root.sprout === 'object' ? root.sprout : {};

root.sprout = {
    ...previous,
    version: SCHEMA_VERSION,
    config,
    components: registry,
    createComponent,
    registerComponent: (name) => registerComponent(name, registry),
    getComponent,
    SchemaRenderer,
};

if (typeof window !== 'undefined') {
    window.sprout = root.sprout;
}

export default root.sprout;
