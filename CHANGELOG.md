# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.7] - 2026-07-28

### Performance

Frontend Blade rendering of deeply nested component trees. Measured on a `button` schema: ~1.24ms → ~0.010ms per component render.

- Reuse a single `TailwindMerge` instance and memoize merge results. `TailwindMerge::instance()` rebuilds the entire merged config on every call, which dominated render cost on nested trees
- Cache immutable `compose()` schemas per component class, so nested instances of the same component do not rebuild their schema
- Build root attributes and structure in one `SchemaRenderer::renderComponent()` pass instead of two, sharing instance IDs and prop lookups
- Cache prop lookups, presets/tokens, class strategy, debug flag, and reflected class metadata (public props, view path, theme-view existence)
- Defer Tailwind class merging until `ClassFactory::get()` so each node merges once instead of on every `apply()` (mirrored in the JS runtime)
- Skip schema auto-discovery on public WordPress pages; `shouldAutoDiscover()` is now console/admin only. Run `wp acorn parity:cache` to keep admin/editor loads warm

### Added

- `TailwindClassStrategy::flush()` for resetting the shared merger and memo cache

## [1.0.6] - 2026-07-27

### Fixed

- `parity:make` closes each match outcome with `->end()` and scaffolds root components under `App\View\Components` (not `Components\Components`)
- `parity:doctor` fails on WordPress hosts when the editor manifest or `dist/parity.js` is missing
- Release workflow uses `composer update` (no committed lockfile) and `softprops/action-gh-release@v3`

### Changed

- `gehrisandro/tailwind-merge-php` is a hard `require` so the default `tailwind` class strategy works after `composer require`
- Security policy covers the latest 1.x line and `main`

## [1.0.5] - 2026-07-27

### Fixed

- Grant `contents: write` so the release workflow can publish GitHub Release assets
- Use Pest 4 on the Laravel 13 matrix and keep Composer requires in `--dev`
- Bare-Laravel smoke uses the passthrough class strategy without requiring Tailwind Merge

## [1.0.4] - 2026-07-27

### Fixed

- Apply Pint formatting so release CI style checks pass

## [1.0.3] - 2026-07-27

### Fixed

- Rename `tests/fixtures` to `tests/Fixtures` so PSR-4 class fixtures load on Linux CI
- Correct bare-Laravel smoke import to `Parity\Component`
- Disable Composer advisory blocking in CI so matrix installs can resolve Laravel versions with published advisories

## [1.0.2] - 2026-07-27

### Fixed

- Regenerate `package-lock.json` with Node 22 so CI `npm ci` stays in sync

## [1.0.1] - 2026-07-27

### Fixed

- Commit `package-lock.json` so CI `npm ci` and npm caching work
- Bump GitHub Actions to `checkout`/`setup-node` v5 and Node 22

### Added

- VS Code Intelephense include paths for Laravel framework stubs

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
