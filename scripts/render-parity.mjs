import { readFileSync } from 'node:fs';
import { SchemaRenderer } from '../resources/js/render/schemaRenderer.js';

const input = readFileSync(0, 'utf8');
const cases = JSON.parse(input);
const results = {};

for (const [name, caseItem] of Object.entries(cases)) {
    const schemaName = caseItem.schema?.name ?? 'component';

    globalThis.sprout = {
        config: {
            ...(caseItem.config ?? {}),
            [schemaName]: caseItem.schema,
        },
    };

    const renderer = new SchemaRenderer(schemaName, caseItem.props, caseItem.schema);
    const attributes = renderer.renderComponentAttributes();

    results[name] = {
        className: attributes.className ?? '',
    };
}

process.stdout.write(JSON.stringify(results));
