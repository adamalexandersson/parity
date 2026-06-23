import { readFileSync } from 'node:fs';
import { SchemaRenderer } from '../resources/js/render/schemaRenderer.js';
import { collectDefaultSlotTargets } from '../resources/js/render/slotResolver.js';

const input = readFileSync(0, 'utf8');
const cases = JSON.parse(input);
const results = {};

function normalizeAttributes(attributes = {}) {
    return {
        class: attributes.className ?? attributes.class ?? '',
    };
}

function normalizeNode(node) {
    const children = node.children ?? {};

    return {
        path: node.path ?? null,
        tag: node.tag ?? null,
        fragment: Boolean(node.fragment),
        slot: node.slot ?? null,
        attributes: normalizeAttributes(node.attributes ?? {}),
        children: normalizeStructure(children),
    };
}

function normalizeStructure(structure = {}) {
    return Object.fromEntries(
        Object.entries(structure)
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([key, node]) => [key, normalizeNode(node)])
    );
}

for (const [name, caseItem] of Object.entries(cases)) {
    const schema = caseItem.schema;
    const componentName = schema.name ?? name;

    const renderer = new SchemaRenderer(componentName, caseItem.props ?? {}, schema);
    const structure = renderer.renderStructure();
    const defaultSlot = schema.defaultSlot ?? null;

    results[name] = {
        structure: normalizeStructure(structure),
        slotTargets: collectDefaultSlotTargets(structure, defaultSlot),
    };
}

process.stdout.write(JSON.stringify(results));
