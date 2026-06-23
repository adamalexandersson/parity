# Sprout schema v1.0

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

Operators: `truthy`, `falsy`, `equals`, `notEquals`, `any`, `all`.

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

Each `case` or `default` carries an `outcomes` array. Outcome types:

- `classes` — `{ "type": "classes", "value": "px-4 py-2" }`
- `attr` — `{ "type": "attr", "name": "aria-expanded", "value": true }`
- `style` — `{ "type": "style", "property": "opacity", "value": "0.5" }`

Match groups may include a `condition` object (use `MatchBuilder::onlyWhen()` / `unlessProp()` in PHP).

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

Schema-only components do **not** require a theme Blade file. When no view exists at `resources/views/components/{namespace}/{name}.blade.php`, Sprout renders the built-in shell (`Sprout::shell`):

```blade
<{{ $element }} {!! $attributes->merge($attr) !!}>
    {!! $content ?? $slot !!}
</{{ $element }}>
```

Variables available in the default shell:

| Variable | Description |
|----------|-------------|
| `$element` | Root tag (`button`, `a`, `h2`, …) after linkable resolution |
| `$attr` | Schema-rendered attributes (classes, href, type, …) |
| `$attributes` | Laravel `ComponentAttributeBag` from the caller |
| `$content` | Rendered structure tree (components with `children`) |
| `$slot` | Default slot content (slot-only components like Heading) |

**Override:** Create a theme blade at the path above to replace the default shell. Sprout detects it automatically — no PHP changes needed. Use overrides for Alpine.js markup, conditional wrappers, or any custom DOM outside the schema structure.

Configure the fallback view via `config('sprout.shell_view')` (default: `Sprout::shell`).

## Node helpers

- `Node::holdsNamedSlot('image')` — declare a named slot on a wrapper element (renders only when slot content exists)
- `Node::holdsDefaultSlot()` — mark a node as the default slot container
- `Node::namedSlot('icon')` — fragment-only named slot (prefer `holdsNamedSlot` on a wrapper when classes are needed)
- `Node::mappedComponent()` — dynamic child component from prop map
- `->attr('type')->from('type')->unless('link.href')->end()` — conditional attributes

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
