export const SCHEMA_VERSION = '1.0';

export function assertSchemaVersion(config) {
    if (!config?.schemaVersion) {
        return;
    }

    if (config.schemaVersion !== SCHEMA_VERSION) {
        // eslint-disable-next-line no-console
        console.warn(
            `[Parity] Schema version mismatch. Expected ${SCHEMA_VERSION}, got ${config.schemaVersion}.`
        );
    }
}
