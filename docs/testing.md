# Testing

Parity keeps Blade and the editor honest with golden fixtures that run through both renderers.

## Commands

```bash
composer test          # Pest suite
composer test:parity   # npm run build && pest --filter=Parity
npm test               # Vitest (resources/js + tests/vite)
composer analyse       # PHPStan
vendor/bin/pint --test
```

## What the parity suite covers

| Test | Role |
|------|------|
| `tests/ParityTest.php` | Class-string snapshots from `parity-cases.php`; PHP output must match JS via `scripts/render-parity.mjs` |
| `tests/StructureParityTest.php` | Structure snapshots from `structure-cases.php`; PHP/JS via `scripts/structure-parity.mjs` |
| `tests/ParityCoverageTest.php` | Walks fixture schemas; every enum in the JSON Schema feature catalog must appear somewhere |

Helpers live in `tests/Pest.php` (`normalizeClassString`, `runNodeParityScript`, …).

## Fixture format

```php
// tests/Fixtures/Cases/parity-cases.php
'button-sm-primary' => [
    'schema' => require __DIR__.'/../Schemas/button.php',
    'props' => [
        'size' => 'sm',
        'themeColor' => 'primary',
        'themeType' => 'solid',
    ],
    // optional:
    // 'config' => [
    //     'tokens' => [...],
    //     'presets' => [...],
    //     'classes' => ['strategy' => 'passthrough'],
    // ],
],
```

Schema files return `Component::make(...)->toSchema()`.

## Adding a fixture

1. Add a schema file under `tests/Fixtures/Schemas/` that returns a serialized schema.
2. Register a case in `tests/Fixtures/Cases/parity-cases.php` and/or `structure-cases.php`.
3. Add the expected snapshot in the test's `expectedClassSnapshots()` / `expectedStructures()` map.
4. Run `composer test:parity`. Coverage picks up new casts, operators, and modes automatically — missing features fail `ParityCoverageTest`.

PHP class fixtures used by shell/reflector tests live in `tests/Fixtures/Components/` (`Parity\Tests\Fixtures\Components`).

## Vitest

JS unit tests live beside the modules they cover (`resources/js/**/*.test.js`) and under `tests/vite/` for the Vite plugin. They run with `npm test` and do not need PHP.

## CI

`.github/workflows/tests.yml` builds the editor bundle, runs Vitest, then Pest / Pint / PHPStan across PHP 8.2–8.4 and Laravel 11–13. A separate `bare-laravel` job installs a fresh Laravel app with no WordPress and renders a Parity component.
