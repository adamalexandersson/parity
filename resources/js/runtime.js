import { createComponent, registerComponent } from './render/createComponent.jsx';
import { SchemaRenderer } from './render/schemaRenderer.js';
import { SCHEMA_VERSION } from './schema/version.js';

const registry = {};

function bootstrapComponents() {
    const configs = window.componentConfig ?? {};

    Object.keys(configs).forEach((name) => {
        if (['common', 'schemaVersion', 'icons', 'iconAjaxUrl', 'iconAjaxNonce', 'tokens'].includes(name)) {
            return;
        }

        if (typeof configs[name] === 'object' && configs[name]?.schemaVersion) {
            registerComponent(name, registry);
        }
    });
}

bootstrapComponents();

function getComponent(name) {
    if (registry[name]) {
        return registry[name];
    }

    const config = window.componentConfig?.[name];

    if (config && typeof config === 'object' && config.schemaVersion) {
        return registerComponent(name, registry);
    }

    return function SproutComponentFallback() {
        return null;
    };
}

window.sprout = {
    version: SCHEMA_VERSION,
    components: registry,
    createComponent,
    registerComponent: (name) => registerComponent(name, registry),
    getComponent,
    SchemaRenderer,
};

export default window.sprout;
