import { describe, expect, it, beforeEach } from 'vitest';
import { isAlpineAttribute, shouldSuppressAlpine } from './alpineAttributes.js';
import { normalizeDomAttributes } from './domAttributes.js';
import { SchemaRenderer } from '../render/schemaRenderer.js';

describe('alpine attributes', () => {
    beforeEach(() => {
        globalThis.sprout = { config: { editor: { alpine: 'suppress' } } };
    });

    it('detects alpine directives and bind shorthand', () => {
        expect(isAlpineAttribute('x-data')).toBe(true);
        expect(isAlpineAttribute('x-on:click')).toBe(true);
        expect(isAlpineAttribute('x-bind:aria-expanded')).toBe(true);
        expect(isAlpineAttribute(':class')).toBe(true);
        expect(isAlpineAttribute('aria-controls')).toBe(false);
        expect(isAlpineAttribute('id')).toBe(false);
    });

    it('suppresses alpine attrs in editor normalization by default', () => {
        const attrs = normalizeDomAttributes({
            id: 'sprout-demo-panel',
            'aria-controls': 'sprout-demo-panel',
            'x-on:click': "toggle('sprout-demo-panel')",
            'x-show': 'open',
            class: 'panel',
        });

        expect(attrs.id).toBe('sprout-demo-panel');
        expect(attrs['aria-controls']).toBe('sprout-demo-panel');
        expect(attrs.className).toBe('panel');
        expect(attrs['x-on:click']).toBeUndefined();
        expect(attrs['x-show']).toBeUndefined();
        expect(shouldSuppressAlpine()).toBe(true);
    });

    it('keeps alpine attrs when emit is configured', () => {
        globalThis.sprout = { config: { editor: { alpine: 'emit' } } };

        const attrs = normalizeDomAttributes({
            'x-show': 'open',
            id: 'panel',
        });

        expect(attrs['x-show']).toBe('open');
        expect(attrs.id).toBe('panel');
    });

    it('interpolates ids in schema renderer output', () => {
        const schema = {
            schemaVersion: '1.0',
            name: 'demo',
            attributes: [
                { name: 'id', uniqueId: 'root' },
                { name: 'x-init', value: "init('{root}')" },
            ],
            children: {
                panel: {
                    tag: 'div',
                    attributes: [
                        { name: 'id', uniqueId: 'panel' },
                        { name: 'x-show', value: "isOpen('{panel}')" },
                    ],
                },
            },
        };

        const renderer = new SchemaRenderer('demo', { instanceId: 'demo' }, schema);
        const root = renderer.renderComponentAttributes();
        const structure = renderer.renderStructure();

        expect(root['x-init']).toBe("init('sprout-demo-root')");
        expect(structure.panel.attributes['x-show']).toBe("isOpen('sprout-demo-panel')");

        const editorAttrs = normalizeDomAttributes(structure.panel.attributes);
        expect(editorAttrs.id).toBe('sprout-demo-panel');
        expect(editorAttrs['x-show']).toBeUndefined();
    });
});
