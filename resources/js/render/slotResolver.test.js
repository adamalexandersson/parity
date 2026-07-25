import { describe, expect, it } from 'vitest';
import {
    collectDefaultSlotTargets,
    hasStructureChildren,
    shouldRenderDefaultSlot,
} from './slotResolver.js';

describe('slotResolver', () => {
    it('treats empty children as absent', () => {
        expect(hasStructureChildren([])).toBe(false);
        expect(hasStructureChildren({})).toBe(false);
        expect(hasStructureChildren({ child: { tag: 'div' } })).toBe(true);
    });

    it('collects default slot targets from structure', () => {
        const structure = {
            body: {
                path: 'body',
                children: {
                    content: {
                        path: 'body.content',
                        slot: { name: null, default: true },
                        children: {},
                    },
                },
            },
        };

        expect(collectDefaultSlotTargets(structure, 'body.content')).toEqual(['body.content']);
        expect(shouldRenderDefaultSlot(
            structure.body.children.content,
            'body.content',
            'content',
            'body.content',
        )).toBe(true);
    });
});
