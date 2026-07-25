# Sprout

Schema-driven cross-runtime components for **Laravel Blade** and **WordPress Gutenberg** (via a WordPress host adapter).

Define a component once in PHP. Sprout renders it identically on the front end (Blade) and — on WordPress — in the block editor (`window.sprout`).

## Supported environments

| Environment | Blade / schema | Gutenberg editor |
|-------------|----------------|------------------|
| Laravel | Yes | No (use your own bridge if needed) |
| Sage / Acorn + WordPress | Yes | Yes (WordPress host auto-detected) |

## Install

### Laravel

```bash
composer require adamalexandersson/sprout
php artisan vendor:publish --tag=sprout
```

Register the provider if your app does not auto-discover packages:

```php
Sprout\Providers\SproutServiceProvider::class,
```

### Sage / Acorn (WordPress)

```bash
composer require adamalexandersson/sprout
wp acorn vendor:publish --tag=sprout
```

Acorn auto-registers the provider via `extra.acorn`. The WordPress host activates when `add_action` exists and boots Gutenberg assets.

Publish tags:

```bash
wp acorn vendor:publish --tag=sprout
wp acorn vendor:publish --tag=sprout-config
wp acorn vendor:publish --tag=sprout-common
```

### Gutenberg editor exports (WordPress themes)

```bash
wp acorn sprout:manifest
wp acorn sprout:generate-editor-exports
```

Theme `package.json`:

```json
{
  "scripts": {
    "sprout:manifest": "wp acorn sprout:manifest",
    "sprout:exports": "wp acorn sprout:generate-editor-exports",
    "sprout:sync": "npm run sprout:manifest && npm run sprout:exports",
    "predev": "npm run sprout:sync",
    "prebuild": "npm run sprout:sync"
  }
}
```

Vite aliases (see [docs/editor.md](docs/editor.md)):

```js
'@sprout/runtime': path.resolve(__dirname, 'vendor/adamalexandersson/sprout/resources/js/editor/runtime.js'),
'@sprout/components': '/resources/js/sprout/components.js',
```

## Theme integration

Sprout auto-discovers components from `app/View/Components` with a static `schema()` method. No theme Blade shell is required for schema-only components — Sprout provides a default wrapper. Override by adding `resources/views/components/{namespace}/{name}.blade.php` when needed.

Extend Sprout from any service provider that boots with your theme — for example an integration `Init.php` or `AppServiceProvider`:

```php
use Sprout\Facades\Sprout;

public function boot(): void
{
    Sprout::transforms()->register('imageUrl', function ($value) {
        return is_numeric($value) ? wp_get_attachment_url((int) $value) : $value;
    });

    // WordPress only — applied by the WordPress host
    add_filter('sprout/editor/config', function (array $config) {
        return array_merge($config, [
            'icons' => [],
        ]);
    });
}
```

Shared Tailwind class maps for `->includeCommon('cols')` live in `config/sprout/common.php`.

Class composition defaults to Tailwind Merge. Switch to passthrough (concatenate + dedupe) with:

```php
// config/sprout.php
'classes' => [
    'strategy' => 'passthrough', // or 'tailwind'
],
```

Mapped icon nodes that call `@svg()` require optional [`blade-ui-kit/blade-icons`](https://github.com/blade-ui-kit/blade-icons). Without it, Sprout still renders the mapped dynamic component and skips the SVG child.

Force a host with `SPROUT_HOST=laravel` or `SPROUT_HOST=wordpress` (default: auto-detect).

## Author a component

```php
namespace App\View\Components\Ui;

use Sprout\Component;
use Sprout\Node;
use Sprout\View\Component as SproutComponent;

class Button extends SproutComponent
{
    public function __construct(
        public string $size = 'md',
        public string $themeColor = 'default',
        public string $themeType = 'solid',
        public array|string|null $link = null,
    ) {
        parent::__construct(...func_get_args());
    }

    public static function schema(): array
    {
        return Component::make('button', tag: 'button')
            ->linkable('link')
            ->classes('inline-flex items-center gap-x-1 font-semibold')
            ->match('size')
                ->case('sm')->classes('px-4 py-2 text-sm')
                ->case('lg')->classes('px-8 py-4 text-lg')
                ->default()->classes('px-6 py-3 text-sm')
                ->end()
            ->match('themeColor', 'themeType')
                ->case('primary', 'solid')->classes('bg-primary-500 text-white')
                ->default()->classes('bg-gray-900 text-white')
                ->end()
            ->slot('content')
            ->children([
                Node::namedSlot('icon'),
                Node::make('label')->fragment()->richText('label', __('Button text…', 'sage')),
                Node::make('content')->fragment()->holdsDefaultSlot(),
            ])
            ->toSchema();
    }
}
```

Front end:

```blade
<x-ui.button size="lg" theme-color="primary">Read more</x-ui.button>
```

## Use in a Gutenberg block

Declare `sprout` as a script dependency:

```jsx
import { Button } from '@sprout/components';

export default function Edit({ attributes, setAttributes }) {
    return (
        <Button {...attributes} editable setAttributes={setAttributes} />
    );
}
```

Sprout auto-enqueues the precompiled editor bundle and injects `window.sprout.config` with your component schemas.

## Commands

```bash
wp acorn sprout:make Button --ui
wp acorn sprout:manifest
wp acorn sprout:generate-editor-exports
wp acorn sprout:safelist
wp acorn sprout:cache
wp acorn sprout:clear
wp acorn sprout:doctor
```

On plain Laravel, use `php artisan` instead of `wp acorn`.

## Schema version

Sprout uses schema version **1.0**. Every serialized component includes `schemaVersion: "1.0"`. The editor runtime warns when versions mismatch.

See [docs/schema-v1.md](docs/schema-v1.md) for the full schema reference and [docs/editor.md](docs/editor.md) for Gutenberg/manifest details.

## Contributing / building the editor bundle

```bash
npm install
npm run build
npm test
composer test
composer analyse
```

The compiled bundle is written to `dist/sprout.js` and shipped with the Composer package.

## Escape hatch

Prefer schema attributes (including Alpine helpers) for interactive markup. For exceptional cases only — register a hand-written React component via `window.sprout.registerComponent('name')`, or add a theme Blade shell override when the schema cannot express a dynamic loop.

## License

MIT
