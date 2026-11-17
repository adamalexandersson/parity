import { beforeEach, describe, expect, it } from 'vitest';
import { ClassFactory } from './factories.js';

beforeEach(() => {
    globalThis.parity = {
        config: {
            classes: {
                strategy: 'tailwind',
            },
        },
    };
});

describe('ClassFactory', () => {
    it('merges conflicting utilities once at get()', () => {
        const classes = new ClassFactory();

        classes.apply('p-2');
        classes.apply('text-sm p-4');
        classes.apply('text-lg');

        expect(classes.get()).toBe('p-4 text-lg');
        // Stable across repeated get() without new applies.
        expect(classes.get()).toBe('p-4 text-lg');
    });

    it('keeps configured font-size names beside text colors', () => {
        globalThis.parity.config.classes.fontSize = ['body', 'preamble'];

        const classes = new ClassFactory();

        classes.apply('px-7 text-body');
        classes.apply('text-button-primary-solid-text');

        expect(classes.get()).toBe('px-7 text-body text-button-primary-solid-text');
    });

    it('deduplicates without conflict resolution for passthrough', () => {
        globalThis.parity.config.classes.strategy = 'passthrough';

        const classes = new ClassFactory();

        classes.apply('p-2');
        classes.apply('p-4');
        classes.apply('p-2');

        expect(classes.get()).toBe('p-2 p-4');
    });
});
