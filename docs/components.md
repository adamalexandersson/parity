# Components

Author schema-driven components with a static `compose()` method. One PHP definition renders identically in Blade and — on WordPress — in the Gutenberg editor.

## Create a component

Extend `Parity\View\Component` (or use the `ComposesMarkup` trait and implement `Composable`). Composition boots lazily — do **not** call `parent::__construct(...func_get_args())`.

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
                Node::make('label')->fragment()->richText('label', __('Button text…')),
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

Scaffold with:

```bash
wp acorn parity:make Button --ui
# or: php artisan parity:make Button --ui
```

## Boot lifecycle

On the first `data()` / `render()` call:

1. `static::compose()` loads the schema
2. `prepare()` runs (empty by default — override for prop normalization)
3. Props are collected, linkable tags resolve, attributes and structure build

## Authoring API at a glance

| Method | Role |
|--------|------|
| `Component::make($name, tag:)` | Root builder |
| `classes($string)` | Literal class rule |
| `match(...$props)` / `case` / `default` / `end` | Conditional class (and other) outcomes |
| `when` / `unless` | Condition the last class rule, attr, style, or match group |
| `visible` / `hidden` | Whether the **node** renders |
| `preset($name)` | Shared map from `config('parity.presets')` |
| `token($group, $name)` | Token lookup from `config('parity.tokens')` |
| `attr()` / `style()` | Open builders — must close with `->end()` |
| `slot()` / `slot('name')` | Default / named slot holders |
| `children([Node::make(...)])` | Nested structure |
| `linkable($prop, $tag = 'a')` | Swap the root tag when the prop is truthy |
| `xData`, `xShow`, `xOn`, … | Alpine bindings on the published front end |
| `toSchema()` | Serialize; injects `schemaVersion`; throws if builders are unclosed |

Full reference: [schema-v1.md](schema-v1.md). Class strategies and presets: [classes.md](classes.md). Value transforms: [transforms.md](transforms.md).

Coming from a hand-rolled theme component layer? The [Sleak theme's upgrade guide](https://github.com/adamalexandersson/sleak) (`docs/migrating-from-dsl.md`) is a worked example of migrating to this API.
