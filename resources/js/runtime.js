import { createComponent, registerComponent } from './render/createComponent.jsx';
import { SchemaRenderer } from './render/schemaRenderer.js';
import { SCHEMA_VERSION } from './schema/version.js';

const registry = {};

function getSproutConfig() {
    return window.sprout?.config ?? {};
}

function bootstrapComponents() {
    const configs = getSproutConfig();

    Object.keys(configs).forEach((name) => {
        if (['common', 'schemaVersion', 'tokens'].includes(name)) {
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

    return function SproutComponentFallback() {
        return null;
    };
}

window.sprout = {
    ...(typeof window.sprout === 'object' ? window.sprout : {}),
    version: SCHEMA_VERSION,
    config,
    components: registry,
    createComponent,
    registerComponent: (name) => registerComponent(name, registry),
    getComponent,
    SchemaRenderer,
};

export default window.sprout;
