import { describe, expect, it } from 'vitest';
import { isVoidElement, VOID_ELEMENTS } from './voidElements.js';

describe('voidElements', () => {
    it('includes the standard void set', () => {
        expect(VOID_ELEMENTS).toContain('img');
        expect(VOID_ELEMENTS).toContain('input');
        expect(VOID_ELEMENTS).toContain('br');
    });

    it('detects void tags case-insensitively', () => {
        expect(isVoidElement('IMG')).toBe(true);
        expect(isVoidElement('div')).toBe(false);
    });
});
