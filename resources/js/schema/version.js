export const SCHEMA_VERSION = '1.0';

/**
 * Extract the major segment from a schema version string (e.g. "1.0" → "1").
 *
 * @param {string} version
 * @returns {string}
 */
export function schemaMajor(version) {
    if (typeof version !== 'string' || version === '') {
        return '';
    }

    return version.split('.', 1)[0] || version;
}

/**
 * True when the given version shares SCHEMA_VERSION's major.
 *
 * @param {string} version
 * @returns {boolean}
 */
export function isSchemaCompatible(version) {
    return schemaMajor(version) === schemaMajor(SCHEMA_VERSION);
}

/**
 * Warn only on a major mismatch. Minor differences are additive and tolerated.
 *
 * @param {{ schemaVersion?: string }|null|undefined} config
 */
export function assertSchemaVersion(config) {
    if (!config?.schemaVersion) {
        return;
    }

    if (isSchemaCompatible(config.schemaVersion)) {
        return;
    }

    // eslint-disable-next-line no-console
    console.warn(
        `[Parity] Schema major version mismatch. Expected ${SCHEMA_VERSION}, got ${config.schemaVersion}.`
    );
}
