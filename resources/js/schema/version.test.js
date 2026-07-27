import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    SCHEMA_VERSION,
    assertSchemaVersion,
    isSchemaCompatible,
    schemaMajor,
} from './version.js';

describe('schema version policy', () => {
    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('extracts the major segment', () => {
        expect(schemaMajor('1.0')).toBe('1');
        expect(schemaMajor('2.3')).toBe('2');
        expect(schemaMajor('')).toBe('');
    });

    it('treats same-major versions as compatible', () => {
        expect(isSchemaCompatible(SCHEMA_VERSION)).toBe(true);
        expect(isSchemaCompatible('1.9')).toBe(true);
        expect(isSchemaCompatible('2.0')).toBe(false);
    });

    it('warns only on a major mismatch', () => {
        const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});

        assertSchemaVersion({ schemaVersion: '1.5' });
        expect(warn).not.toHaveBeenCalled();

        assertSchemaVersion({ schemaVersion: '2.0' });
        expect(warn).toHaveBeenCalledOnce();
        expect(warn.mock.calls[0][0]).toContain('major version mismatch');
        expect(warn.mock.calls[0][0]).toContain('2.0');
    });

    it('ignores missing schemaVersion', () => {
        const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});

        assertSchemaVersion({});
        assertSchemaVersion(null);

        expect(warn).not.toHaveBeenCalled();
    });
});
