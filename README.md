# Sprout

Schema-driven cross-runtime components for Roots Sage/Acorn themes.

Define a component once in PHP. Sprout renders it identically on the front end (Blade) and in the Gutenberg editor (precompiled React runtime exposed as `window.sprout`).

## Install

```bash
composer require adamalexandersson/sprout
wp acorn sprout:install
```

Register the published provider in `app/Providers/ThemeServiceProvider.php`:

```php
$this->app->register(\Sprout\Providers\SproutServiceProvider::class);
$this->app->register(SproutServiceProvider::class);
```

The package provider registers Sprout itself. The theme provider (`app/Providers/SproutServiceProvider.php`) is your extension point for transforms and editor config.

### Publish options

```bash
# Publish theme provider + config/sprout/common.php stub
wp acorn sprout:install

# Publish package config only
wp acorn vendor:publish --tag=sprout --provider="Sprout\\Providers\\SproutServiceProvider"

# Overwrite existing published files
wp acorn sprout:install --force
```

### Gutenberg editor exports (optional)

If your theme uses Vite and named Sprout component imports in block editors:

```bash
wp acorn sprout:generate-editor-exports
```

Add to `package.json`:

```json
{
  "scripts": {
    "sprout:exports": "wp acorn sprout:generate-editor-exports",
    "prebuild": "npm run sprout:exports"
  }
}
```

Configure the output path in `config/sprout.php` under `editor.exports_path` (default: `resources/js/sprout/components.js`).

During local development with a path repository:

```json
"repositories": [
    {
        "type": "path",
        "url": "../../../../../../Packages/sprout",
        "options": { "symlink": true }
    }
],
"require": {
    "adamalexandersson/sprout": "@dev"
}
```

## Theme integration

Sprout auto-discovers components from `app/View/Components` with a static `schema()` method. No theme Blade shell is required for schema-only components — Sprout provides a default wrapper. Override by adding `resources/views/components/{namespace}/{name}.blade.php` when needed.

Extend Sprout in `app/Providers/SproutServiceProvider.php`:

```php
use Sprout\Facades\Sprout;

public function boot(): void
{
    Sprout::transforms()->register('imageUrl', function ($value) {
        return is_numeric($value) ? wp_get_attachment_url((int) $value) : $value;
    });

    add_filter('sprout/editor/config', function (array $config) {
        return array_merge($config, [
            'icons' => [],
        ]);
    });
}
```

Shared Tailwind class maps for `->includeCommon('cols')` live in `config/sprout/common.php`.

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

Sprout auto-enqueues the precompiled editor bundle and injects `window.componentConfig` with your component schemas.

## Commands

```bash
wp acorn sprout:install
wp acorn sprout:make Button --ui
wp acorn sprout:generate-editor-exports
wp acorn sprout:safelist
wp acorn sprout:cache
wp acorn sprout:clear
wp acorn sprout:doctor
```

## Schema version

Sprout uses schema version **1.0**. Every serialized component includes `schemaVersion: "1.0"`. The editor runtime warns when versions mismatch.

See [docs/schema-v1.md](docs/schema-v1.md) for the full schema reference.

## Contributing / building the editor bundle

Package maintainers build the precompiled editor runtime:

```bash
npm install
npm run build
composer test
```

The compiled bundle is written to `dist/sprout.js` and shipped with the Composer package. Tagged releases run the GitHub Actions release workflow, which rebuilds `dist/` before publishing.

## Escape hatch

For complex interactive components (accordions, tabs), register a hand-written React component under the same name via `window.sprout.registerComponent('accordion')` or theme-specific editor code.

## License

MIT
