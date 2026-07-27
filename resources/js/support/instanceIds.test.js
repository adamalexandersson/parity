import { describe, expect, it } from 'vitest';
import { InstanceIds, interpolateIds, shouldInterpolateIds } from './instanceIds.js';

describe('instanceIds interpolation', () => {
    it('detects placeholders including escaped forms', () => {
        expect(shouldInterpolateIds('panel-{panel}')).toBe(true);
        expect(shouldInterpolateIds('literal {{panel}}')).toBe(true);
        expect(shouldInterpolateIds('no placeholders')).toBe(false);
        expect(shouldInterpolateIds(42)).toBe(false);
    });

    it('always interpolates {name} when declared', () => {
        const ids = new InstanceIds('demo', { instanceId: 'x' });
        ids.declare('panel');

        expect(interpolateIds('aria-controls="panel-{panel}"', ids)).toBe(
            'aria-controls="panel-sprout-x-panel"'
        );
    });

    it('escapes {{name}} to literal {name}', () => {
        const ids = new InstanceIds('demo', { instanceId: 'x' });
        ids.declare('panel');

        expect(interpolateIds('show {{panel}} then {panel}', ids)).toBe(
            'show {panel} then sprout-x-panel'
        );
    });

    it('leaves unknown placeholders intact outside debug', () => {
        const ids = new InstanceIds('demo', { instanceId: 'x' });

        expect(interpolateIds('panel-{missing}', ids)).toBe('panel-{missing}');
    });
});
