# Contributing to Parity

## Setup

```bash
composer install
npm install
npm run build
composer test
```

The compiled editor bundle is written to `dist/parity.js` and shipped with the Composer package (committed — see Phase 5 distribution rationale).

This package does **not** commit `composer.lock` (library convention). Local and CI installs resolve from `composer.json`; the release workflow uses `composer update --prefer-dist` explicitly for the same reason.

## Architecture

- **PHP authoring** — `src/Component.php`, `src/Node.php`, builders in `src/Builders/`
- **PHP renderer** — `src/Render/SchemaRenderer.php` + `resources/views/structure.blade.php`
- **Editor runtime** — `resources/js/` compiled to `dist/parity.js`

## Parity tests

Golden fixtures live in `tests/Fixtures/`. PHPUnit compares PHP renderer output with the bundled JS renderer via `scripts/render-parity.mjs`:

```bash
composer test:parity
npm test
composer analyse
```

See [testing.md](testing.md) for adding fixtures.

## Release

Tag with `v*` to trigger `.github/workflows/release.yml`, which builds `dist/`, runs the full suite, and attaches `parity.zip` plus `dist/parity.js` to the GitHub release.
