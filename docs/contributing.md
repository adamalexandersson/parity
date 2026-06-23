# Contributing to Sprout

## Setup

```bash
composer install
npm install
npm run build
composer test
```

## Architecture

- **PHP authoring** — `src/Component.php`, `src/Node.php`, builders in `src/Builders/`
- **PHP renderer** — `src/Render/SchemaRenderer.php` + `resources/views/structure.blade.php`
- **Editor runtime** — `resources/js/` compiled to `dist/sprout.js`

## Parity tests

Golden fixtures live in `tests/fixtures/`. PHPUnit compares PHP renderer output with the bundled JS renderer via `scripts/render-parity.mjs`:

```bash
composer test:parity
```

## Release

Tag with `v*` to trigger `.github/workflows/release.yml`, which builds `dist/` and attaches a release archive.
