# Parity

Schema-driven cross-runtime components for **Laravel Blade** and **WordPress Gutenberg** (via a WordPress host adapter).

Define a component once in PHP. Parity renders it identically on the front end (Blade) and — on WordPress — in the block editor (`window.parity`).

## Supported environments

| Environment | Blade / schema | Gutenberg editor |
|-------------|----------------|------------------|
| Laravel | Yes | No (use your own bridge if needed) |
| Sage / Acorn + WordPress | Yes | Yes (WordPress host auto-detected) |

## Install

Full guide: [docs/installation.md](docs/installation.md) (four steps).

```bash
composer require adamalexandersson/parity
```

Optional config:

```bash
# Laravel
php artisan vendor:publish --tag=parity
php artisan vendor:publish --tag=parity-presets

# Sage / Acorn
wp acorn vendor:publish --tag=parity
wp acorn vendor:publish --tag=parity-presets
```

Register the provider if your Laravel app does not auto-discover packages:

```php
Parity\Providers\ParityServiceProvider::class,
```

Acorn auto-registers via `extra.acorn`. The WordPress host activates when `add_action` exists and boots Gutenberg assets.

### Gutenberg editor (WordPress / Sage)

One Vite plugin line:

```js
import parity from './vendor/adamalexandersson/parity/vite.js';

export default defineConfig({
    plugins: [parity()],
});
```

Regenerate the committed manifest before builds (needs a booted host):

```json
{
  "scripts": {
    "parity:manifest": "wp acorn parity:manifest",
    "predev": "npm run parity:manifest",
    "prebuild": "npm run parity:manifest"
  }
}
```

Import components with `import { Card } from '@parity/components'`. See [docs/editor.md](docs/editor.md).

## Theme integration

Parity auto-discovers components from `app/View/Components` that implement `Parity\Contracts\Composable` (a static `compose()` method). No theme Blade shell is required for schema-only components — Parity provides a default wrapper. Override by adding `resources/views/components/{namespace}/{name}.blade.php` when needed.

Extend Parity from any service provider that boots with your theme — for example an integration `Init.php` or `AppServiceProvider`:

```php
use Parity\Facades\Parity;

public function boot(): void
{
    Parity::transforms()->register('imageUrl', function ($value) {
        return is_numeric($value) ? wp_get_attachment_url((int) $value) : $value;
    });

    // WordPress only — applied by the WordPress host
    add_filter('parity/editor/config', function (array $config) {
        return array_merge($config, [
            'icons' => [],
        ]);
    });
}
```

Shared Tailwind class maps for `->preset('cols')` live in `config/parity/presets.php` (`config('parity.presets')`).

Class composition defaults to Tailwind Merge (`gehrisandro/tailwind-merge-php`, suggested). Install that package when using the default strategy, or switch to passthrough (concatenate + dedupe):

```php
// config/parity.php
'classes' => [
    'strategy' => 'passthrough', // or 'tailwind'
],
```

The editor runtime bundles `tailwind-merge` into `dist/parity.js`, so the JS side always has conflict resolution when strategy is `tailwind`.

Mapped icon nodes that call `@svg()` require optional [`blade-ui-kit/blade-icons`](https://github.com/blade-ui-kit/blade-icons). Without it, Parity still renders the mapped dynamic component; outside production it throws naming the missing package, and in production it skips the SVG child.

Force a host with `PARITY_HOST=laravel` or `PARITY_HOST=wordpress` (default: auto-detect).

## Author a component

Extend `Parity\View\Component` (or use `ComposesMarkup` and implement `Composable`). Composition boots lazily — no `parent::__construct(...func_get_args())`.

```php
namespace App\View\Components\Ui;

use Parity\Component;
use Parity\Node;
use Parity\View\Component as ParityComponent;

class Button extends ParityComponent
{
    public function __construct(
        public string $size = 'md',
        public string $themeColor = 'default',
        public string $themeType = 'solid',
        public array|string|null $link = null,
    ) {}

    public static function compose(): array
    {
        return Component::make('button', tag: 'button')
            ->linkable('link')
            ->classes('inline-flex items-center gap-x-1 font-semibold')
            ->match('size')
                ->case('sm')->classes('px-4 py-2 text-sm')->end()
                ->case('lg')->classes('px-8 py-4 text-lg')->end()
                ->default()->classes('px-6 py-3 text-sm')->end()
                ->end()
            ->match('themeColor', 'themeType')
                ->case('primary', 'solid')->classes('bg-primary-500 text-white')->end()
                ->default()->classes('bg-gray-900 text-white')->end()
                ->end()
            ->children([
                Node::make('icon')->fragment()->slot('icon'),
                Node::make('label')->fragment()->richText('label', __('Button text…', 'sage')),
                Node::make('content')->fragment()->slot(),
            ])
            ->toSchema();
    }
}
```

Front end:

```blade
<x-ui.button size="lg" theme-color="primary">Read more</x-ui.button>
```

See [docs/schema-v1.md](docs/schema-v1.md) for `preset()`, `token()`, nested `component()`, `when`/`unless` vs `visible`/`hidden`, and always-on `{name}` ID interpolation.
## Use in a Gutenberg block

Declare `parity` as a script dependency:

```jsx
import { Button } from '@parity/components';

export default function Edit({ attributes, setAttributes }) {
    return (
        <Button {...attributes} editable setAttributes={setAttributes} />
    );
}
```

Parity auto-enqueues the precompiled editor bundle and injects `window.parity.config` with your component schemas.

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

Parity uses schema version **1.0**. Every serialized component includes `schemaVersion: "1.0"`. The editor runtime warns when versions mismatch.

See [docs/schema-v1.md](docs/schema-v1.md) for the full schema reference and [docs/editor.md](docs/editor.md) for Gutenberg/manifest details.

## Contributing / building the editor bundle

```bash
npm install
npm run build
npm test
composer test
composer analyse
```

The compiled bundle is written to `dist/parity.js` and shipped with the Composer package.

## Escape hatch

Prefer schema attributes (including Alpine helpers) for interactive markup. For exceptional cases only — register a hand-written React component via `window.parity.registerComponent('name', MyComponent)`, or add a theme Blade shell override when the schema cannot express a dynamic loop. Called with only a name, `registerComponent` builds the usual schema-driven wrapper.

## License

MIT
