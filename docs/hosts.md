# Hosts

Parity talks to the surrounding framework through `Parity\Contracts\Host`. Two adapters ship in the package; resolution is automatic.

## Contract

```php
interface Host
{
    public function name(): string;
    public function escAttr(string $value): string;
    public function escUrl(string $value): string;
    public function filter(string $hook, mixed $value, mixed ...$args): mixed;
    public function path(string $relative): string;
    public function url(string $relative): string;
    public function jsonEncode(mixed $value, int $flags = 0): string;
    public function isDebug(): bool;
    public function shouldAutoDiscover(): bool;
}
```

## Resolution

`config('parity.host')` / `PARITY_HOST`:

| Value | Adapter |
|-------|---------|
| `wordpress` | `WordPressHost` |
| `laravel` | `LaravelHost` |
| `null` (default) | `WordPressHost` when `add_action` exists, otherwise `LaravelHost` |

## Laravel vs WordPress

| Method | LaravelHost | WordPressHost |
|--------|-------------|---------------|
| `escUrl` | Passthrough | `esc_url()` |
| `filter` | In-memory `listen()` | `apply_filters()` |
| `path` | `base_path()` | `get_theme_file_path()` |
| `url` | `asset()` | Theme `vendor/adamalexandersson/parity/…` URI, else `plugins_url()` |
| `isDebug` | `config('app.debug')` | `SCRIPT_DEBUG` |
| `shouldAutoDiscover` | Always true | Console, or after WordPress `init` |

## Editor assets

On WordPress, `EditorAssets` resolves the precompiled bundle at:

1. `vendor/adamalexandersson/parity/dist/parity.js` via the host `path()`
2. Package root `dist/parity.js` (dev / direct clone)

The script version is a content hash of that file so cache busting survives deploys that rewrite mtimes. Override the public URL with the `parity/editor/script_url` filter.

## Filters

WordPress hosts expose package hooks through `apply_filters`. Common ones:

- `parity/editor/config` — merge host data into the injected editor config
- `parity/editor/script_url` — override the bundle URL
