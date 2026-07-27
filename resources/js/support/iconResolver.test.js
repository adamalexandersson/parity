import { beforeEach, describe, expect, it, vi } from 'vitest';
import { getIconResolver, registerIconResolver, resolveIcon } from './iconResolver.js';

describe('iconResolver', () => {
    beforeEach(() => {
        registerIconResolver(null);
    });

    it('returns null without a registered resolver', () => {
        expect(resolveIcon('heroicon-o-chevron-down', {
            component: { ref: 'heroicon-o-chevron-down' },
        })).toBeNull();
    });

    it('calls the host resolver with name and element context', () => {
        const resolver = vi.fn(() => 'icon-element');
        registerIconResolver(resolver);

        expect(resolveIcon('heroicon-o-chevron-down', {
            component: {
                ref: 'ui.icon',
                class: 'size-7',
                props: { foo: 'bar' },
            },
        })).toBe('icon-element');

        expect(resolver).toHaveBeenCalledWith({
            name: 'heroicon-o-chevron-down',
            ref: 'ui.icon',
            className: 'size-7',
            props: { foo: 'bar' },
        });
        expect(getIconResolver()).toBe(resolver);
    });

    it('swallows resolver errors and returns null', () => {
        registerIconResolver(() => {
            throw new Error('boom');
        });

        expect(resolveIcon('x', { component: { ref: 'x' } })).toBeNull();
    });
});
