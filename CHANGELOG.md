# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

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
