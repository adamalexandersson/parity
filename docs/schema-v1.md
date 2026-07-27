# Sprout schema v1.0

Machine-readable contract: [`resources/schema/component.schema.json`](../resources/schema/component.schema.json) (used by `sprout:doctor` and parity coverage).

Every component schema includes:

```json
{
  "schemaVersion": "1.0",
  "name": "button",
  "tag": "button",
  "defaultSlot": "content",
  "namedSlots": [],
  "linkable": { "prop": "link", "tag": "a" },
  "classRules": [
    { "classes": "inline-flex", "condition": null },
    { "classes": "gap-x-2", "condition": { "prop": "arrow", "operator": "truthy" } }
  ],
  "matches": [
    {
      "props": ["size"],
      "cases": [
        { "values": ["sm"], "outcomes": [{ "type": "classes", "value": "px-4 py-2" }] }
      ],
      "default": [{ "type": "classes", "value": "px-6 py-3" }]
    }
  ],
  "attributes": [
    { "name": "href", "source": "link.href", "cast": "string", "condition": null },
    { "name": "type", "source": "type", "cast": "string", "condition": { "prop": "link.href", "operator": "falsy" } }
  ],
  "styles": [
    { "property": "background-image", "source": "backgroundImage", "cast": "imageUrl", "cssUrl": true, "condition": { "prop": "backgroundImage", "operator": "truthy" } }
  ],
  "children": {}
}
```

Serialized keys that stay stable across the authoring rename: `defaultSlot`, `namedSlots`, `matches[].preset`, nested `component` (`ref` / `from` / `map` / `class` / `props`). There is no `interpolateIds` key — `{name}` interpolation is always on.

## Authoring API

Components implement `Sprout\Contracts\Composable` (usually by extending `Sprout\View\Component`, which uses the `ComposesMarkup` trait):

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
            ->children([
                Node::make('content')->fragment()->slot(),
            ])
            ->toSchema();
    }
}
```

| Authoring | Role |
|-----------|------|
| `compose()` | Static entry point that returns the serialized schema |
| `prepare()` | Optional instance hook after schema load, before attributes/structure build |
| `ComposesMarkup` / `Composable` | Lazy boot from `data()` / `render()` — no `parent::__construct(...func_get_args())` |

Discovery finds classes that implement `Composable` under `config('sprout.components')`. Call `sprout:doctor` to flag pre-Phase-2 names (`schema()`, `initialize()`, `includeCommon()`, …).

### Boot lifecycle

1. First `data()` or `render()` call runs `bootComposition()` once.
2. `static::compose()` loads the schema.
3. `prepare()` runs (override for prop normalization).
4. Public props are collected, linkable tag resolution runs, then attributes and structure are built.

Do not call `parent::__construct(...func_get_args())` — the trait does not depend on constructor cooperation.

### Open-builder guard

Builders opened with `attr()`, `style()`, `match()`, or `component()` must be closed with `->end()` before `toSchema()`. Leaving one open throws `SchemaException` listing the unclosed builder name(s).

## Condition object

```json
{ "prop": "featured", "operator": "truthy" }
{ "prop": "link.href", "operator": "falsy" }
{ "prop": "size", "operator": "equals", "value": "lg" }
{ "prop": "overlay", "operator": "notEquals", "value": "" }
```

Operators:

| Operator | Meaning |
|----------|---------|
| `truthy` / `falsy` | Non-empty / empty-ish prop |
| `equals` / `notEquals` (`==` / `!=`) | Strict equality |
| `any` / `all` | Nested condition groups (no `prop` required) |
| `in` / `notIn` | Value in / not in array `value` |
| `gt` / `gte` / `lt` / `lte` | Numeric compare (non-numeric → false) |
| `contains` | String contains substring, or array contains item |
| `empty` / `notEmpty` | `null`, `false`, `''`, `[]`, whitespace-only string |

Compound conditions (no `prop` required) use a nested `conditions` array:

```json
{
  "operator": "any",
  "conditions": [
    { "prop": "arrow", "operator": "truthy" },
    { "prop": "icon", "operator": "truthy" }
  ]
}
{
  "operator": "all",
  "conditions": [
    { "prop": "link.href", "operator": "truthy" },
    { "prop": "external", "operator": "falsy" }
  ]
}
```

## `when` / `unless` vs `visible` / `hidden`

Two different scopes:

| Verb | Where | Meaning |
|------|-------|---------|
| `when` / `unless` | Last class rule, attribute, style, or match group | Conditions the thing just declared |
| `visible` / `hidden` | Node | Conditions whether the node renders at all |

```php
->classes('gap-x-2')->when('arrow')
->attr('type')->from('type')->unless('link.href')->end()

->match('layout')
    ->when('featured')
    ->case('grid')->classes('grid')->end()
    ->end()

Node::make('badge', tag: 'span')
    ->visible('showBadge')
    ->classes('inline-flex')
```

`MatchBuilder::when()` / `unless()` attach a `condition` object on the match group (same shape as attribute conditions).

## Class-rule modes

Literal class rules omit `mode`. Token rules use `mode: "token"` with `tokenGroup` and `token` (from `->token()`):

```php
->token('padding', 'md')
```

Reserved for Phase 5 (BEM) — recognized by the schema and ignored by renderers until implemented:

```json
{ "mode": "element", "element": "header", "condition": null }
{ "mode": "modifier", "modifier": "type", "condition": null }
```

## Nested components

Map a prop (or a fixed ref) to a nested Blade / editor component via the component builder:

```php
Node::make('icon', tag: 'div')
    ->component('ui.icon')
        ->from('type')
        ->map([
            'info' => 'heroicon-o-information-circle',
            'error' => 'heroicon-o-x-circle',
        ])
        ->class('size-7')
        ->props(['aria-hidden' => true])
        ->end()
```

Serializes as a nested object (not flat `componentRef` keys):

```json
{
  "component": {
    "ref": "ui.icon",
    "from": "type",
    "map": {
      "info": "heroicon-o-information-circle",
      "error": "heroicon-o-x-circle"
    },
    "class": "size-7",
    "props": { "aria-hidden": true }
  }
}
```

Omit `from` / `map` for a fixed ref; omit `class` / `props` when unused.

Mapped icon names can render via Blade Icons (`@svg()`). That path is optional: install `blade-ui-kit/blade-icons` when you need SVG mapping. Without it, Sprout still renders the mapped dynamic component wrapper; outside production it throws a clear exception naming the missing package, and in production it skips the `@svg()` child.

## Style casts

Resolve a prop to a CSS value with `->cast('name')`. Built-in casts: `string`, `boolean`, `integer`, `url`, `cssUrl`.

For CSS properties that require `url(...)`, chain `->asCssUrl()` after the source cast so resolution and CSS formatting stay separate:

```php
->style('background-image')
    ->from('backgroundImage')
    ->cast('imageUrl')
    ->asCssUrl()
    ->when('backgroundImage')
    ->end()
```

Register theme-specific transforms (e.g. `imageUrl` for attachment IDs) via `Sprout::transforms()->register()`. Use `cssUrl` only for the final CSS wrapper — not inside custom transforms.

## Slots

Mark slot holders on nodes. The component-level `defaultSlot` path is derived automatically — do not declare it twice.

```php
->children([
    Node::make('image', tag: 'div')->slot('image'),
    Node::make('body', tag: 'div')->children([
        Node::make('content', tag: 'div')->slot(),
        Node::make('footer', tag: 'div')->slot('footer'),
    ]),
])
```

| Call | Meaning |
|------|---------|
| `->slot()` | Default slot holder |
| `->slot('name')` | Named slot holder |

Nested default slots produce a dotted `defaultSlot` path (e.g. `body.content`). Rendered structure nodes include a `path` key used for slot matching in Blade and the editor runtime.

Every component schema includes a `namedSlots` array auto-collected from the structure tree. The editor runtime uses this list to route slot props dynamically — no hardcoded slot names.

## Presets

Reference shared class maps from `config('sprout.presets')` (published as `config/sprout/presets.php`):

```php
use Sprout\Builders\ConditionBuilder;

Component::make('container')
    ->preset('verticalSpacing', condition: ConditionBuilder::notEquals('noVerticalSpacing', true)->toArray())
```

Serializes as a match entry with `preset`:

```json
{
  "props": ["verticalSpacing"],
  "preset": "verticalSpacing",
  "condition": { "prop": "noVerticalSpacing", "operator": "notEquals", "value": true }
}
```

Optional second argument remaps the prop name: `->preset('cols', as: 'columns')`.

When a map named `{key}Nested` exists (e.g. `verticalSpacingNested` alongside `verticalSpacing`), `preset('{key}')` applies **both** class maps for the same prop value. Use this for Gutenberg editor spacing that must pierce `block-editor-inner-blocks` wrappers via `space-nested-y-*` while keeping `space-y-*` for frontend output.

## Match outcomes

Each `case` or `default` carries an `outcomes` array. Both Blade and the editor apply all three types:

- `classes` — `{ "type": "classes", "value": "px-4 py-2" }`
- `attr` — `{ "type": "attr", "name": "aria-expanded", "value": true }`
- `style` — `{ "type": "style", "property": "opacity", "value": "0.5" }`

```php
->match('state')
    ->case('disabled')->attr('disabled', true)->style('opacity', '0.5')->classes('is-disabled')->end()
    ->end()
```

Match groups may include a `condition` object via `MatchBuilder::when()` / `unless()`.

## Alpine bindings

Alpine directives are ordinary schema attributes. Prefer the helpers on `Node` / `Component`:

```php
Component::make('accordion', tag: 'div')
    ->uniqueId('root')
    ->xData('accordion({ single: false })')
    ->xInit("init('{root}')")
    ->children([
        Node::make('trigger', tag: 'button')
            ->uniqueId('trigger')
            ->attr('aria-controls')->idRef('panel')->end()
            ->xOn('click', "toggle('{panel}')")
            ->xBind('aria-expanded', "isOpen('{panel}') ? 'true' : 'false'"),
        Node::make('panel', tag: 'div')
            ->uniqueId('panel')
            ->xShow("isOpen('{panel}')")
            ->xCloak()
            ->attrs(['x-collapse' => true]),
    ]);
```

`{name}` placeholders in attribute values resolve against the same `InstanceIds` bag as `uniqueId` / `idRef`. Interpolation is always on when a value contains `{…}`. Escape a literal brace form with `{{name}}` (renders as `{name}`). Unknown placeholders throw in debug mode and stay literal otherwise.

Full-form only: `@click` is rejected by `AttributeFactory` (invalid attribute name). Prefer `x-on:*` and `x-bind:*` over Alpine bind shorthand (`:class`); the editor may still emit `:…` when `editor.alpine` is `emit`.

In the Gutenberg canvas, Alpine attributes are **suppressed by default** (`config('sprout.editor.alpine')` / `window.sprout.config.editor.alpine` = `suppress`). Themes that boot Alpine in the editor iframe should set `emit` so directives reach the DOM. Blade output is never stripped. See [`docs/editor.md`](editor.md).

## Unique IDs and accessibility

Generate stable per-instance IDs and cross-reference them from sibling nodes:

```php
Node::make('label', tag: 'label')
    ->attr('for')->idRef('field')->end()
    ->slot('label'),

Node::make('field', tag: 'input')
    ->uniqueId('field')
    ->attr('aria-describedby')->idRef('hint')->end(),

Node::make('hint', tag: 'p')
    ->uniqueId('hint')
    ->slot('hint'),
```

Schema shape:

```json
{ "name": "id", "uniqueId": "field" }
{ "name": "for", "idRef": "field" }
{ "name": "aria-controls", "idRef": "panel" }
```

IDs render as `sprout-{instanceKey}-{name}`. Prefer an explicit `instanceId` (or `id`) prop for stable output across Blade and the editor; otherwise Sprout fingerprints scalar props. Pass a fixed `instanceId` in parity tests.

## Boolean attributes and React mapping

HTML boolean attributes (`disabled`, `checked`, `required`, `readonly`, `multiple`, `selected`, `open`, `hidden`, `controls`, `autoplay`, `muted`, `loop`, `playsinline`, `defer`, `async`, `novalidate`, `reversed`, `itemscope`) omit when false in both runtimes.

In the Gutenberg editor, attribute names are mapped for React (`class` → `className`, `for` → `htmlFor`, plus common SVG camelCase such as `viewBox` / `strokeWidth`). `data-*`, `aria-*`, and `item*` pass through. Root `<svg>` nodes receive `xmlns="http://www.w3.org/2000/svg"`.

Custom casts registered on PHP `TransformRegistry` remain PHP-only until a later phase; the editor mirrors built-in casts (`string`, `boolean`, `integer`, `url`, `cssUrl`).

### Value normalization in matches

Props are normalized before comparison so PHP, Blade, and the editor stay in sync:

| Prop value | Normalized |
|------------|------------|
| `true` | `'true'` |
| `false` | `'false'` |
| `1`, `2`, … (int/float) | string number (`'1'`, `'2'`, …) |
| `null`, `''` | `''` |
| other strings | trimmed string |

**Booleans:** Prefer real PHP `bool` props and `->case(true)` / `->case(false)` in schemas. Blade string forms (`'true'`, `'1'`, `'false'`, `'0'`) still match bool cases at comparison time — numeric props like `level: 1` are not coerced to booleans.

**Optional enums:** When a prop is `null` or empty, it matches a case labelled `'default'` (e.g. `->case(true, 'default')`). You can also normalize in `prepare()` or default the constructor to `'default'` for clarity.

## Theme blade views (optional)

Schema-only components do **not** require a theme Blade file. When no view exists at `resources/views/components/{namespace}/{name}.blade.php`, Sprout renders the built-in shell (`Sprout::shell`). Void root tags omit the closing tag.

Variables available in the default shell:

| Variable | Description |
|----------|-------------|
| `$element` | Root tag (`button`, `a`, `h2`, …) after linkable resolution |
| `$attr` | Schema-rendered attributes (classes, href, type, …) |
| `$attributes` | Laravel `ComponentAttributeBag` from the caller |
| `$content` | Rendered structure tree (components with `children`) |
| `$slot` | Default slot content (slot-only components like Heading) |

**Override:** Create a theme blade at the path above to replace the default shell. Sprout detects it automatically — no PHP changes needed. Prefer schema attributes (including Alpine helpers) for structure and behavior. Use overrides only for exceptional cases — dynamic loops the schema cannot express yet, or a full custom shell.

Configure the fallback view via `config('sprout.shell_view')` (default: `Sprout::shell`).

## Node helpers

- `->slot()` / `->slot('name')` — default or named slot holder (`defaultSlot` / `namedSlots` derived on serialize)
- `->component('x')->from()->map()->class()->props()->end()` — nested / mapped child component
- `->preset('cols')` — shared class map from `config('sprout.presets')`
- `->token('group', 'name')` — token class rule (`mode: "token"`)
- `->uniqueId('panel')` — set this node's `id` from a generated unique ID
- `->attr('for')->idRef('field')->end()` — reference a generated unique ID
- `->attr('type')->from('type')->unless('link.href')->end()` — conditional attributes
- `->visible('prop')` / `->hidden('prop')` — whether the node renders
- `->when()` / `->unless()` on classes (and builders) — condition the thing just declared
- `->xData()` / `->xInit()` / `->xShow()` / `->xCloak()` / `->xOn()` / `->xBind()` — Alpine attribute helpers

## Ordinary HTML compositions

Representative fixtures under `tests/fixtures/html-compositions.php` cover forms (`label`/`for`, `input`, `select`/`option`, `textarea`, `fieldset`/`legend`), `picture`/`source`/`img` (`srcset`, `sizes`, `loading`, `decoding`), `video`/`track`, tables (`thead`/`th`/`scope`/`colgroup`/`col`), and FAQ-style microdata (`itemscope` / `itemtype` / `itemprop`).

## Errors and debug DX

When `app.debug` / `sprout.editor.debug` is on (PHP), or `window.sprout.config.debug` is on (editor), unknown match outcome types throw `Sprout\Exceptions\SchemaException` (component + path in the message). The editor `createComponent` path catches render errors and shows an in-block alert panel in debug mode instead of only logging to the console. Unclosed builders fail at `toSchema()` with the open builder names.

## Slot resolution

Default and named slot insertion is handled by `Sprout\Render\SlotResolver` (PHP), `resources/js/render/slotResolver.js` (editor), and `structure.blade.php` (frontend). All three share the same rules:

- A node is a default-slot target when `shouldRenderDefaultSlot()` is true: it matches `defaultSlot` (by path or key, or `slot.default`), has **no structure children**, and is not a RichText node.
- Empty structure children (`[]` / `{}`) must **not** block slot rendering — only nodes with actual child elements in the schema tree count as having structure children.
- Named slot nodes without content are skipped entirely.

Parity coverage:

- `SlotResolverTest` — slot target paths
- `StructureParityTest` — PHP/JS structure tree + slot targets (requires `npm run build`)
- `ParityTest` — PHP/JS component class strings
