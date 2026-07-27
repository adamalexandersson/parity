# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-07-27

Initial public release. Schema version **1.0**.

### Added

- Schema-driven components with a static `compose()` API (`Parity\View\Component`, `ComposesMarkup`, `Composable`)
- PHP and Gutenberg renderers kept at parity, with coverage enforced from the JSON Schema
- Host adapters: `LaravelHost` and `WordPressHost` behind `Parity\Contracts\Host`
- First-party Vite plugin (`vite.js`) resolving `@parity/runtime`, `@parity/components`, and `@parity/canvas`
- Canvas helpers: `bridgeCanvasConfig()`, opt-in `bootAlpine()`
- Commands: `parity:make`, `parity:manifest`, `parity:safelist`, `parity:cache`, `parity:clear`, `parity:doctor`
- Presets and tokens (`->preset()`, `->token()`), pluggable class strategies (`tailwind`, `passthrough`)
- Alpine schema bindings with dual-driver editor convention (React owns canvas state)
- Documentation suite: installation, components, schema-v1, editor, transforms, classes, hosts, testing, upgrading
- Content-hash cache busting for the editor script; committed `dist/parity.js` for zero-config installs
- CI matrix across PHP 8.2–8.4 and Laravel 11–13, plus a bare-Laravel smoke job

### Schema

- Schema version `1.0` on every serialized component
- Runtime and `parity:doctor` warn on **major** mismatch only; minor differences are tolerated
- Reserved class-rule modes `element` and `modifier` for upcoming BEM support (1.1) — additive, no schema major

### Notes

Pre-1.0 internal builds were never published for external use. There is no upgrade path from those builds — install `^1.0` and author against the current API. Migrating a Sleak theme off the old `BaseComponent` DSL is documented in that theme's `docs/migrating-from-dsl.md`, not in this package.
