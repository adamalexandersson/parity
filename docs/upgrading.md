# Upgrading

Parity-to-Parity versioning. For migrating a Sleak theme off the old `BaseComponent` DSL, see that theme's `docs/migrating-from-dsl.md` — it is not a package concern.

## Two versions travel together

| Version | What it covers |
|---------|----------------|
| **Package** | Semver on the PHP and JS API: builder methods, config keys, service contracts, host adapters, published files |
| **Schema** (`Parity\Schema\Version::CURRENT`, currently `1.0`) | The serialized schema contract consumed by the editor runtime |

Every serialized component includes `schemaVersion: "1.0"`.

### Schema bumps

- A schema **minor** bump means additive keys or new `mode` values. Older runtimes ignore them; newer runtimes read older schemas.
- A schema **major** bump means a breaking serialization change and requires a package major.
- A package major does **not** necessarily imply a schema major.

BEM support (package 1.1) is additive and does not bump the schema major.

### Mismatch behaviour

| Surface | Major mismatch | Minor difference |
|---------|----------------|------------------|
| Editor runtime (`assertSchemaVersion`) | `console.warn` | Silent — tolerated |
| `parity:doctor` | Warning, counted as an issue | Info line only |

Every schema change is noted in `CHANGELOG.md` with its schema-version impact.

## From pre-1.0

Versions before the `1.0.0` tag were never published for external use. There is no supported upgrade path from those internal builds — install `^1.0` and author against the current `compose()` API documented in [components.md](components.md) and [schema-v1.md](schema-v1.md).
