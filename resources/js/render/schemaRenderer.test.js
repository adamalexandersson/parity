import { beforeEach, describe, expect, it } from 'vitest';
import { SchemaRenderer } from './schemaRenderer.js';

beforeEach(() => {
    globalThis.sprout = {
        config: {
            tokens: {
                gap: { md: 'gap-4' },
            },
            classes: {
                strategy: 'tailwind',
            },
        },
    };
});

describe('SchemaRenderer conditions', () => {
    it('evaluates any and all compound conditions', () => {
        const schema = {
            schemaVersion: '1.0',
            name: 'demo',
            classRules: [
                { classes: 'base', condition: null },
                {
                    classes: 'any-hit',
                    condition: {
                        operator: 'any',
                        conditions: [
                            { prop: 'arrow', operator: 'truthy' },
                            { prop: 'icon', operator: 'truthy' },
                        ],
                    },
                },
                {
                    classes: 'all-hit',
                    condition: {
                        operator: 'all',
                        conditions: [
                            { prop: 'href', operator: 'truthy' },
                            { prop: 'external', operator: 'falsy' },
                        ],
                    },
                },
            ],
        };

        const attrs = new SchemaRenderer('demo', {
            arrow: true,
            icon: false,
            href: 'https://example.com',
            external: false,
        }, schema).renderComponentAttributes();

        expect(attrs.className).toContain('any-hit');
        expect(attrs.className).toContain('all-hit');
    });

    it('skips reserved class-rule modes', () => {
        const schema = {
            schemaVersion: '1.0',
            name: 'demo',
            classRules: [
                { classes: 'keep', condition: null },
                { mode: 'element', element: 'header', classes: 'skip-me', condition: null },
                { mode: 'token', tokenGroup: 'gap', token: 'md', classes: '', condition: null },
            ],
        };

        const attrs = new SchemaRenderer('demo', {}, schema).renderComponentAttributes();

        expect(attrs.className).toContain('keep');
        expect(attrs.className).toContain('gap-4');
        expect(attrs.className).not.toContain('skip-me');
    });

    it('applies attr and style match outcomes', () => {
        const schema = {
            schemaVersion: '1.0',
            name: 'demo',
            matches: [
                {
                    props: ['state'],
                    cases: [
                        {
                            values: ['disabled'],
                            outcomes: [
                                { type: 'classes', value: 'is-disabled' },
                                { type: 'attr', name: 'disabled', value: true },
                                { type: 'style', property: 'opacity', value: '0.5' },
                            ],
                        },
                    ],
                    default: [],
                },
            ],
        };

        const attrs = new SchemaRenderer('demo', { state: 'disabled' }, schema).renderComponentAttributes();

        expect(attrs.className).toContain('is-disabled');
        expect(attrs.disabled).toBe(true);
        expect(attrs.style.opacity).toBe('0.5');
    });

    it('evaluates extended operators and unique ids', () => {
        const schema = {
            schemaVersion: '1.0',
            name: 'demo',
            classRules: [
                { classes: 'hit', condition: { prop: 'size', operator: 'in', value: ['sm', 'md'] } },
                { classes: 'empty-hit', condition: { prop: 'note', operator: 'empty' } },
            ],
            children: {
                field: {
                    tag: 'input',
                    attributes: [
                        { name: 'id', uniqueId: 'field' },
                        { name: 'aria-labelledby', idRef: 'label' },
                    ],
                },
                label: {
                    tag: 'label',
                    attributes: [
                        { name: 'id', uniqueId: 'label' },
                        { name: 'for', idRef: 'field' },
                    ],
                },
            },
        };

        const renderer = new SchemaRenderer('demo', {
            instanceId: 'x',
            size: 'md',
            note: '',
        }, schema);

        const attrs = renderer.renderComponentAttributes();
        const structure = renderer.renderStructure();

        expect(attrs.className).toContain('hit');
        expect(attrs.className).toContain('empty-hit');
        expect(structure.field.attributes.id).toBe('sprout-x-field');
        expect(structure.label.attributes.for).toBe('sprout-x-field');
        expect(structure.field.attributes['aria-labelledby']).toBe('sprout-x-label');
    });
});
