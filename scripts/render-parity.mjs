import { readFileSync } from 'node:fs';
import { SchemaRenderer } from '../resources/js/render/schemaRenderer.js';

const input = readFileSync(0, 'utf8');
const cases = JSON.parse(input);
const results = {};

for (const [name, caseItem] of Object.entries(cases)) {
    globalThis.sprout = {
        config: {
            button: caseItem.schema,
        },
    };

    const renderer = new SchemaRenderer('button', caseItem.props, caseItem.schema);
    const attributes = renderer.renderComponentAttributes();

    results[name] = {
        className: attributes.className ?? '',
    };
}

process.stdout.write(JSON.stringify(results));
