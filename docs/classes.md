# Classes

Parity composes class strings from literal rules, `match` outcomes, presets, tokens, and optional CSS naming rules (BEM / kebab / states). The strategy controls how those tokens are **merged** after generation.

## Strategies

Configured in `config/parity.php`:

```php
'classes' => [
    'strategy' => env('PARITY_CLASS_STRATEGY', 'tailwind'),
],
```

| Strategy | Behaviour | Requirement |
|----------|-----------|-------------|
| `tailwind` (default) | Conflict resolution via [tailwind-merge-php](https://github.com/gehrisandro/tailwind-merge-php) | Bundled as a package `require` |
| `passthrough` | Concatenate and deduplicate tokens | None |

The editor bundle always includes `tailwind-merge`, so the JS side resolves conflicts when the strategy is `tailwind`.

**Tailwind vs CSS-naming:** Generation (`modifier` / `variant` / `element` / `is` / `has`) runs first; the strategy only merges the resulting tokens. Projects that author BEM or kebab class names should set `parity.classes.strategy` to `passthrough` so `@`-breakpoints and `--` modifiers are not treated as Tailwind utilities.

Naming is **not** a class strategy. BEM/kebab stay in `classRules`; strategies remain merge-only.

## Presets

Shared value-to-class maps, referenced with `->preset('cols')`:

```php
// config/parity/presets.php (publish with --tag=parity-presets)
return [
    'cols' => [
        '1' => 'grid-cols-1',
        '2' => 'grid-cols-2',
        '3' => 'grid-cols-3',
    ],
    'justify' => [
        'start' => 'justify-start',
        'center' => 'justify-center',
        'end' => 'justify-end',
    ],
];
```

```php
->preset('cols')
->preset('justify')
```

`parity:doctor` warns when a schema references a preset key that is not configured.

## Tokens

Named token tables for `->token('p', 'md')` style lookups:

```php
// config/parity.php
'tokens' => [
    'p' => [
        'sm' => 'p-2',
        'md' => 'p-4',
        'lg' => 'p-6',
    ],
],
```

Serializes as a class rule with `mode: "token"`.

## CSS naming (BEM, kebab, states)

Ship as package **1.1**. Additive — no schema major bump. Pure Tailwind components that only call `->classes()` / `->match()` / `->preset()` are unchanged: the block class is **not** auto-emitted unless `category`, `block()`, or a naming rule (`element` / `modifier` / `variant` / `is` / `has`) is present.

### Config

Three sibling roots (exported to the editor as `window.parity.config.bem|variant|state`):

```php
'bem' => [
    'categories' => [
        'component' => 'c-',
        'object' => 'o-',
        'organizer' => 'o-',
        'module' => 'm-',
        'utility' => 'u-',
    ],
    'element' => '__',
    'modifier' => '--',
    'breakpoint' => '@',
],

'variant' => [
    'element' => '-',
    'join' => '-',
    'format' => 'kebab', // shared value formatting for modifier + variant segments
],

'state' => [
    'is' => 'is-',
    'has' => 'has-',
],
```

Category names accept singular/plural and are case-insensitive (`Components`, `organizer`, `utilities`).

### BEM badge

```php
Component::make('status-badge')
    ->block('badge') // override CSS block when the schema slug differs
    ->category('component')
    ->modifier('pill')
    ->modifier('size')
    ->modifier('themeColor', 'theme')
    ->modifier(['themeType', 'themeColor'], 'theme')
    ->is('active')
    ->has('icon')
    ->children([
        Node::make('content', tag: 'span')
            ->element('label')
            ->modifier('size')
            ->slot(),
    ]);
// c-badge c-badge--pill c-badge--size-md c-badge--theme-primary c-badge--theme-outline-primary is-active has-icon
//   span.c-badge__label.c-badge__label--size-md
```

- **Block base:** `make('badge')` is the default block name; `->block('badge')` overrides.
- **Effective block:** `categoryPrefix + (block ?? name)` → `c-badge`.
- **Modifier:** key-value (`c-badge--size-md`); boolean prop → key-only (`c-badge--pill`); omit when falsy/empty.
- **Compounds:** `->modifier(['themeType', 'themeColor'], 'theme')` → `c-badge--theme-outline-primary` (omit entire class if any source is empty).

### Kebab badge

```php
Component::make('badge')
    ->variant('pill')
    ->variant('size')
    ->variant('themeColor')
    ->is('active')
    ->children([
        Node::make('content', tag: 'span')
            ->element('label')
            ->variant('size')
            ->slot(),
    ]);
// badge badge-pill badge-md badge-primary is-active
//   span.badge-label.badge-label-md
```

- **variant:** value-only (`badge-md`); boolean → `badge-pill` (prop name as segment).
- Optional `category` still prefixes the block string (`c-badge`) while variants stay kebab-joined.

### States

`->is('active')` and `->has('icon')` emit `is-active` / `has-icon` when the prop (default source = name) is truthy. Prefixes come from `parity.state`. States compose alongside BEM or kebab classes and are never `--is-active`.

### Responsive

| Mode | Pattern | Example |
|------|---------|---------|
| BEM modifier | `{block}@{bp}--{key}-{value}` | `o-grid@md--cols-2` |
| BEM boolean | `{block}@{bp}--{key}` | `o-card@md--featured` |
| Kebab variant | `{block}-{bp}-{value}` | `grid-md-2` |
| Kebab boolean | `{block}-{bp}-{key}` | `card-md-featured` |

```php
Component::make('grid')
    ->category('object')
    ->modifier('colsMd', 'cols', breakpoint: 'md');
// o-grid o-grid@md--cols-2

Component::make('grid')
    ->category('object')
    ->modifier('cols', 'cols', breakpoint: 'md'); // reads colsMd, then cols_md, then cols
// o-grid o-grid@md--cols-2

Component::make('grid')
    ->variant('colsMd', breakpoint: 'md');
// grid grid-md-2
```

### Authoring API

| Method | Role |
|--------|------|
| `->category(string)` | ITCSS/category prefix (Component) |
| `->block(string)` | Override CSS block name (Component; default `make()` name) |
| `->element(string)` | BEM `__el` / kebab `-el` |
| `->modifier(source, ?as, breakpoint: ?string, value: ?string)` | BEM key-value / boolean |
| `->variant(source, breakpoint: ?string, value: ?string)` | Kebab value-only / boolean-as-name |
| `->is(string $name, ?string $source = null)` | `is-{name}` from prop |
| `->has(string $name, ?string $source = null)` | `has-{name}` from prop |

`->when()` / `->unless()` apply to the last class rule as usual.
