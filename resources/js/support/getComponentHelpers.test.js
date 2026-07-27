import { afterEach, describe, expect, it, vi } from 'vitest';
import { isProduction, missingComponentFallback } from './getComponentHelpers.js';

afterEach(() => {
    vi.unstubAllEnvs();
    delete globalThis.parity;
});

describe('getComponentHelpers', () => {
    it('throws for unknown components outside production', () => {
        vi.stubEnv('NODE_ENV', 'test');

        expect(() => missingComponentFallback('missing', ['button'])).toThrow(
            /Unknown component "missing"/,
        );
    });

    it('returns a silent null fallback in production', () => {
        vi.stubEnv('NODE_ENV', 'production');

        const Fallback = missingComponentFallback('missing', []);

        expect(Fallback()).toBeNull();
    });

    it('treats config.debug as non-production', () => {
        vi.stubEnv('NODE_ENV', 'production');
        globalThis.parity = { config: { debug: true } };

        expect(isProduction()).toBe(false);
    });
});
