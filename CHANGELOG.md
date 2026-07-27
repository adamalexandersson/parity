# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Phase 3 — Rename to Parity (breaking)

Hard cutover from Sprout to Parity. No backwards-compatibility aliases.

| Surface | Old | New |
|---------|-----|-----|
| Composer | `adamalexandersson/sprout` | `adamalexandersson/parity` |
| PHP namespace | `Sprout\` | `Parity\` |
| Facade / binding | `Sprout` / `sprout` | `Parity` / `parity` |
| Config | `config/sprout.php`, `sprout.*` | `config/parity.php`, `parity.*` |
| Env | `SPROUT_*` | `PARITY_*` |
| Commands | `sprout:*` | `parity:*` |
| Editor global / handle / dist | `window.sprout`, `sprout`, `dist/sprout.js` | `window.parity`, `parity`, `dist/parity.js` |
| Filters | `sprout/editor/*` | `parity/editor/*` |
| Views | `Sprout::shell` | `Parity::shell` |
| Vite aliases | `@sprout/*` | `@parity/*` |

### Phase 2 — Authoring API (breaking)

Full vocabulary rename; no deprecation aliases. Serialized schema keys stay stable where noted (`defaultSlot`, `namedSlots`, `matches[].preset`, nested `component`).

| Old | New |
|-----|-----|
| `schema()` | `compose()` |
| `initialize()` | `prepare()` |
| `parent::__construct(...func_get_args())` | Removed — lazy boot via `ComposesMarkup` / `Composable` |
| `holdsDefaultSlot()` / `holdsNamedSlot()` / `Node::namedSlot()` | `->slot()` / `->slot('name')` (`defaultSlot` derived automatically) |
| `includeCommon()` / `sprout.common` | `preset()` / `sprout.presets` |
| `->apply()` | `->token()` |
| `mappedComponent()` + flat `componentRef*` keys | `->component('x')->from()->map()->class()->props()->end()` → nested `"component": { ref, from, map, class, props }` |
| `MatchBuilder::onlyWhen()` / `unlessProp()` | `when()` / `unless()` |
| `interpolateIds` opt-out | Removed — `{name}` always interpolates; escape with `{{name}}` |

Also: open-builder guard throws on missing `->end()` before `toSchema()`; `sprout:doctor` reports legacy authoring names with replacements.

### Added

- Working `sprout:cache` / `sprout:clear` with schema + class-map payload; `ConfigCollector` hydrates from cache; `sprout:doctor` fails on stale cache
- `window.sprout.registerIconResolver()` host contract for editor nested `component` / mapped icons
- `window.sprout.registerComponent(name, component?)` accepts an optional hand-written React component
- `EditorConfigBuilder::reservedConfigKeys()` as the single source for doctor/manifest denylists
- `sprout.editor.debug` config key (mirrors PHP SchemaRenderer debug checks)
- `Sprout\Concerns\ComposesMarkup` trait and `Sprout\Contracts\Composable` interface
- Open-builder guard on `toSchema()` for unclosed `attr` / `style` / `match` / `component` builders

### Changed

- `gehrisandro/tailwind-merge-php` moved from `require` to `suggest` (+ `require-dev`); Tailwind strategy throws a clear exception when missing
- Mapped `@svg()` without blade-icons throws outside production instead of silently skipping
- Publish surface reduced to a single `--tag=sprout`
- Structure parity normalizer retains nested `component` fields
- Component discovery resolves `Composable` instead of subclass-only scans
- Docs rewritten around Phase 2 vocabulary (`docs/schema-v1.md`, `README.md`, `docs/editor.md`)

### Removed

- Dead `ComponentRegistry` and `Sprout::components()`
- Hardcoded editor chevron SVG (`componentRefIcons.js`)
- Legacy reserved keys `icons` / `iconAjaxUrl` / `iconAjaxNonce` from doctor/manifest denylists
- Granular publish tags `sprout-config` and `sprout-common`
- Public `interpolateIds` opt-out and flat `componentRef` / `componentMapping*` / `componentClass` / `componentProps` schema keys

### Fixed

- Pest test suite with Orchestra Testbench for application tests
- Vitest unit tests for the editor runtime helpers and schema renderer
- Shared void-element list honored by Blade and React renderers
- Compound `any` / `all` conditions in the JavaScript runtime (parity with PHP)
- Pluggable class strategies (`tailwind`, `passthrough`) via `config('sprout.classes.strategy')`
- JSON Schema validation in `sprout:doctor` (`resources/schema/component.schema.json`)
- Enforced parity feature coverage derived from the JSON Schema enums
- Loud missing-component failures outside production (`SCRIPT_DEBUG` / `config.debug`)
- Lazy editor export wrappers from `sprout:generate-editor-exports`
- PHPStan / Larastan analysis, Dependabot, and matrix CI
- `SECURITY.md`, issue templates, and security notes for Blade unescaped output
- `Host` contract with `LaravelHost` and `WordPressHost` (auto-detect / `SPROUT_HOST`)
- `WordPressServiceProvider` for Gutenberg assets; core provider stays WordPress-free
- `EditorConfigBuilder` for host-agnostic editor config
- `sprout:manifest` committed component manifest with reflected constructor props
- Dumb `@sprout/runtime` exports + generated `components.d.ts`
- Manifest drift checks in `sprout:doctor`
- `docs/editor.md` for Gutenberg/manifest workflow
- Match outcomes `attr` / `style` applied in PHP and JS renderers
- `uniqueId` / `idRef` primitives with deterministic `sprout-{instanceKey}-{name}` IDs
- Condition operators `in` / `notIn`, `gt` / `gte` / `lt` / `lte`, `contains`, `empty` / `notEmpty`
- Shared HTML boolean-attribute list and React/SVG attribute mapping for the editor
- `SchemaException` and in-block editor error panel when debug is enabled
- Fixtures for forms, picture/video, tables, and FAQ microdata
- Alpine attribute helpers (`xData`, `xInit`, `xShow`, `xCloak`, `xOn`, `xBind`) and `{name}` ID placeholders via `InstanceIds`
- Editor Alpine policy (`sprout.editor.alpine` = `suppress` | `emit`, default suppress); `emit` passes Alpine attrs through for themes that boot Alpine in the Gutenberg canvas
- Match `attr` outcomes now interpolate ID placeholders (PHP + JS parity)

### Fixed

- Void HTML elements no longer emit invalid closing tags or crash React
- PHP `any` / `all` conditions no longer short-circuit when `prop` is absent
- Mapped `@svg()` rendering is conditional when `blade-ui-kit/blade-icons` is not installed
- Attribute names are validated before rendering
- Inline editor config encoding escapes `</script>` breakout sequences
- Component discovery no longer requires WordPress `init` on Laravel hosts
- Default shell omits closing tags for void root elements
- Node-level attribute `default` applied consistently via shared `applyAttributes`

### Changed

- Reserved class-rule modes `element` and `modifier` for upcoming BEM support
- `blade-ui-kit/blade-icons` is suggested rather than required
- `roots/acorn` moved from `require` to `suggest`; explicit Illuminate console/view/filesystem/support requires
- `sprout:generate-editor-exports` reads the committed manifest (no discovery at export time)
- Gutenberg documented as WordPress-only
