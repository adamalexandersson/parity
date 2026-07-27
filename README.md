# Parity

Schema-driven cross-runtime components for **Laravel Blade** and **WordPress Gutenberg**.

Define a component once in PHP. Parity renders it identically on the front end (Blade) and — on WordPress — in the block editor (`window.parity`).

## Supported environments

| Environment | Blade / schema | Gutenberg editor |
|-------------|----------------|------------------|
| Laravel | Yes | No (use your own bridge if needed) |
| Sage / Acorn + WordPress | Yes | Yes (WordPress host auto-detected) |

## Install

```bash
composer require adamalexandersson/parity
```

Full four-step guide (config publish, Vite plugin, manifest): [docs/installation.md](docs/installation.md).

## Documentation

| Guide | Contents |
|-------|----------|
| [Installation](docs/installation.md) | Laravel and Sage / Acorn setup |
| [Components](docs/components.md) | Authoring with `compose()` |
| [Schema v1](docs/schema-v1.md) | Full schema reference |
| [Editor](docs/editor.md) | Gutenberg, Vite plugin, canvas helpers |
| [Transforms](docs/transforms.md) | Built-in and custom value casts |
| [Classes](docs/classes.md) | Strategies, presets, tokens |
| [Hosts](docs/hosts.md) | Laravel and WordPress adapters |
| [Testing](docs/testing.md) | Fixtures and parity coverage |
| [Upgrading](docs/upgrading.md) | Package vs schema versioning |

## Commands

```bash
wp acorn parity:make Button --ui
wp acorn parity:manifest
wp acorn parity:safelist
wp acorn parity:cache
wp acorn parity:clear
wp acorn parity:doctor
```

On plain Laravel, use `php artisan` instead of `wp acorn`.

## Schema version

Parity uses schema version **1.0**. The editor runtime warns on a **major** mismatch and tolerates minor differences. See [docs/upgrading.md](docs/upgrading.md).

## Contributing

See [docs/contributing.md](docs/contributing.md). Build the editor bundle with `npm run build` (output: `dist/parity.js`).

## Escape hatch

Prefer schema attributes (including Alpine helpers) for interactive markup. For exceptional cases only — register a hand-written React component via `window.parity.registerComponent('name', MyComponent)`, or add a theme Blade shell override when the schema cannot express a dynamic loop.

## License

MIT
