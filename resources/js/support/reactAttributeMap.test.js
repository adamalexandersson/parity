import { describe, expect, it } from 'vitest';
import { mapAttributeName, mapAttributes, isSvgTag } from './reactAttributeMap.js';
import { normalizeDomAttributes } from './domAttributes.js';
import { BOOLEAN_ATTRIBUTES, isBooleanAttribute } from './booleanAttributes.js';
import { formatSchemaError, schemaErrorPanelProps } from './schemaError.js';
import { fingerprint, resolveInstanceKey } from './instanceIds.js';

describe('react attribute map', () => {
    it('maps class and for', () => {
        expect(mapAttributeName('class')).toBe('className');
        expect(mapAttributeName('for')).toBe('htmlFor');
    });

    it('passes data aria item and alpine attrs through', () => {
        expect(mapAttributeName('data-test')).toBe('data-test');
        expect(mapAttributeName('aria-label')).toBe('aria-label');
        expect(mapAttributeName('itemprop')).toBe('itemprop');
        expect(mapAttributeName('x-on:click')).toBe('x-on:click');
        expect(mapAttributeName('x-bind:class')).toBe('x-bind:class');
        expect(mapAttributeName(':class')).toBe(':class');
    });

    it('keeps alpine attrs when editor.alpine is emit', () => {
        globalThis.sprout = { config: { editor: { alpine: 'emit' } } };

        const attrs = normalizeDomAttributes({
            'x-data': 'accordion({ single: false })',
            'x-on:click': "toggle('panel')",
            id: 'trigger',
        });

        expect(attrs['x-data']).toBe('accordion({ single: false })');
        expect(attrs['x-on:click']).toBe("toggle('panel')");
        expect(attrs.id).toBe('trigger');
    });

    it('maps svg camelCase attrs and sets xmlns', () => {
        expect(isSvgTag('svg')).toBe(true);
        expect(mapAttributeName('viewBox', { svg: true })).toBe('viewBox');
        expect(mapAttributeName('strokeWidth', { svg: true })).toBe('strokeWidth');

        const mapped = mapAttributes({ viewBox: '0 0 10 10' }, { tag: 'svg' });
        expect(mapped.viewBox).toBe('0 0 10 10');
        expect(mapped.xmlns).toBe('http://www.w3.org/2000/svg');
    });
});

describe('boolean attributes', () => {
    it('lists the roadmap boolean attributes', () => {
        expect(BOOLEAN_ATTRIBUTES).toContain('disabled');
        expect(BOOLEAN_ATTRIBUTES).toContain('itemscope');
        expect(isBooleanAttribute('playsinline')).toBe(true);
    });

    it('omits false boolean attributes when normalizing for react', () => {
        const attrs = normalizeDomAttributes({
            disabled: false,
            required: true,
            class: 'btn',
            for: 'field',
        });

        expect(attrs.disabled).toBeUndefined();
        expect(attrs.required).toBe(true);
        expect(attrs.className).toBe('btn');
        expect(attrs.htmlFor).toBe('field');
    });
});

describe('schema error helpers', () => {
    it('formats component and path', () => {
        expect(formatSchemaError({ message: 'bad', path: 'root' }, 'card')).toBe('[card] root: bad');
    });

    it('builds panel props', () => {
        const panel = schemaErrorPanelProps({ message: 'bad' }, 'card');
        expect(panel['data-sprout-error']).toBe('true');
        expect(panel.children).toContain('[card]');
    });
});

describe('instance ids', () => {
    it('is deterministic for explicit instanceId', () => {
        expect(resolveInstanceKey('card', { instanceId: 'demo' })).toBe('demo');
        expect(fingerprint('abc')).toMatch(/^[0-9a-f]+$/);
    });
});
