# Sprout schema v1.0

Machine-readable contract: [`resources/schema/component.schema.json`](../resources/schema/component.schema.json) (used by `sprout:doctor` and parity coverage).

Every component schema includes:

```json
{
  "schemaVersion": "1.0",
  "name": "button",
  "tag": "button",
  "defaultSlot": "content",
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

## Class-rule modes

Literal class rules omit `mode`. Token rules use `mode: "token"` with `tokenGroup` and `token` (from `->apply()`).

Reserved for Phase 5 (BEM) — recognized by the schema and ignored by renderers until implemented:

```json
{ "mode": "element", "element": "header", "condition": null }
{ "mode": "modifier", "modifier": "type", "condition": null }
```

## Mapped components and blade-icons

`Node::mappedComponent()` can render icon names via Blade Icons (`@svg()`). That path is optional: install `blade-ui-kit/blade-icons` when you need SVG mapping. Without it, Sprout still renders the mapped dynamic component wrapper and skips the `@svg()` child.

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

## Nested default slots

Set `defaultSlot` to a dotted path when the slot container is nested:

```php
Component::make('alert')->slot('wrapper.content')
```

Rendered structure nodes include a `path` key (e.g. `wrapper.content`) used for slot matching in Blade and the editor runtime.

## Mapped child components

Map a prop value to a nested Blade component (e.g. alert icon):

```php
Node::make('icon', tag: 'div')
    ->mappedComponent('ui.icon', 'type', [
        'info' => 'heroicon-o-information-circle',
    ], 'size-7')
```

Serializes as `componentRef`, `componentMappingKey`, `componentMapping`, and optional `componentClass`.

## includeCommon with conditions

Reference theme maps from `config('sprout.common')`:

```php
use Sprout\Builders\ConditionBuilder;

Component::make('container')
    ->includeCommon('verticalSpacing', condition: ConditionBuilder::notEquals('noVerticalSpacing', true)->toArray())
```

When a map named `{key}Nested` exists (e.g. `verticalSpacingNested` alongside `verticalSpacing`), `includeCommon('{key}')` applies **both** class maps for the same prop value. Use this for Gutenberg editor spacing that must pierce `block-editor-inner-blocks` wrappers via `space-nested-y-*` while keeping `space-y-*` for frontend output.

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

Match groups may include a `condition` object (use `MatchBuilder::onlyWhen()` / `unlessProp()` in PHP).

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

`{name}` placeholders in attribute values resolve against the same `InstanceIds` bag as `uniqueId` / `idRef`. Interpolation runs automatically when a value contains `{…}`; set `"interpolateIds": false` to disable. Unknown placeholders throw in debug mode and stay literal otherwise.

Full-form only: `@click` / `:class` shorthand is rejected by `AttributeFactory`. Use `x-on:*` and `x-bind:*`.

In the Gutenberg canvas, Alpine attributes are **suppressed by default** (`config('sprout.editor.alpine')` / `window.sprout.config.editor.alpine` = `suppress`). Themes that boot Alpine in the editor iframe should set `emit` so directives reach the DOM. Blade output is never stripped. See [`docs/editor.md`](editor.md).

## Unique IDs and accessibility

Generate stable per-instance IDs and cross-reference them from sibling nodes:

```php
Node::make('label', tag: 'label')
    ->attr('for')->idRef('field')->end()
    ->holdsNamedSlot('label'),

Node::make('field', tag: 'input')
    ->uniqueId('field')
    ->attr('aria-describedby')->idRef('hint')->end(),

Node::make('hint', tag: 'p')
    ->uniqueId('hint')
    ->holdsNamedSlot('hint'),
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

**Optional enums:** When a prop is `null` or empty, it matches a case labelled `'default'` (e.g. `->case(true, 'default')`). You can also normalize in `initialize()` or default the constructor to `'default'` for clarity.

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

- `Node::holdsNamedSlot('image')` — declare a named slot on a wrapper element (renders only when slot content exists)
- `Node::holdsDefaultSlot()` — mark a node as the default slot container
- `Node::namedSlot('icon')` — fragment-only named slot (prefer `holdsNamedSlot` on a wrapper when classes are needed)
- `Node::mappedComponent()` — dynamic child component from prop map
- `Node::uniqueId('panel')` — set this node's `id` from a generated unique ID
- `->attr('for')->idRef('field')->end()` — reference a generated unique ID
- `->attr('type')->from('type')->unless('link.href')->end()` — conditional attributes
- `->xData()` / `->xInit()` / `->xShow()` / `->xCloak()` / `->xOn()` / `->xBind()` — Alpine attribute helpers

## Ordinary HTML compositions

Representative fixtures under `tests/fixtures/html-compositions.php` cover forms (`label`/`for`, `input`, `select`/`option`, `textarea`, `fieldset`/`legend`), `picture`/`source`/`img` (`srcset`, `sizes`, `loading`, `decoding`), `video`/`track`, tables (`thead`/`th`/`scope`/`colgroup`/`col`), and FAQ-style microdata (`itemscope` / `itemtype` / `itemprop`).

## Errors and debug DX

When `app.debug` / `sprout.config.debug` is on, unknown match outcome types throw `Sprout\Exceptions\SchemaException` (component + path in the message). The editor `createComponent` path catches render errors and shows an in-block alert panel in debug mode instead of only logging to the console.

## namedSlots metadata

Every component schema includes a `namedSlots` array auto-collected from the structure tree. The editor runtime uses this list to route slot props dynamically — no hardcoded slot names.

## Slot resolution

Default and named slot insertion is handled by `Sprout\Render\SlotResolver` (PHP), `resources/js/render/slotResolver.js` (editor), and `structure.blade.php` (frontend). All three share the same rules:

- A node is a default-slot target when `shouldRenderDefaultSlot()` is true: it matches `defaultSlot` (by path or key, or `slot.default`), has **no structure children**, and is not a RichText node.
- Empty structure children (`[]` / `{}`) must **not** block slot rendering — only nodes with actual child elements in the schema tree count as having structure children.
- Named slot nodes without content are skipped entirely.

Parity coverage:

- `SlotResolverTest` — slot target paths
- `StructureParityTest` — PHP/JS structure tree + slot targets (requires `npm run build`)
- `ParityTest` — PHP/JS component class strings
