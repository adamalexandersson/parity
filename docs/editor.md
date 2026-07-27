# Sprout editor integration

Gutenberg integration is **WordPress-only**. Plain Laravel applications use Sprout for Blade/schema rendering; they do not enqueue `dist/sprout.js`.

## Globals

| Global | Owner | Contents |
|--------|-------|----------|
| `window.sprout` | Sprout package | Runtime API + `config` (schemas, `presets`, `tokens`, `classes`, `debug`) |
| `window.sleak` | Theme (optional) | Icons and AJAX helpers only — not part of Sprout. Interactive UI belongs on `@sprout/components`. |

Config is injected with `wp_add_inline_script` before the Sprout bundle when the WordPress host is active (`SCRIPT_DEBUG` maps to `config.debug`).

## Manifest workflow

Editor named imports are generated from a committed manifest so the JS build does not need WordPress:

```bash
wp acorn sprout:manifest
wp acorn sprout:generate-editor-exports
```

Or in a theme `package.json`:

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

Paths (configurable in `config/sprout.php`):

- `editor.manifest_path` → `resources/js/sprout/manifest.json`
- `editor.exports_path` → `resources/js/sprout/components.js`
- `editor.types_path` → `resources/js/sprout/components.d.ts`

`sprout:doctor` fails when the manifest drifts from discovered schemas.

## Vite aliases

```js
resolve: {
    alias: {
        '@sprout/runtime': path.resolve(__dirname, 'vendor/adamalexandersson/sprout/resources/js/editor/runtime.js'),
        '@sprout/components': '/resources/js/sprout/components.js',
    },
}
```

Generated exports are thin:

```js
import { createExport } from '@sprout/runtime';
export const Card = createExport('card');
```

`createExport` resolves lazily through `window.sprout.getComponent` on render (never at module evaluation time).

## TypeScript

`components.d.ts` is generated alongside the JS module. Point your TS/JSX config at it, or keep the file next to `components.js` so editors pick up prop types automatically.

## Attributes in the editor

`createComponent` maps HTML attribute names for React (`class` → `className`, `for` → `htmlFor`, SVG camelCase) and coerces boolean attributes. When `window.sprout.config.debug` is true, schema/render errors surface as an in-block alert instead of failing silently. See [`docs/schema-v1.md`](schema-v1.md) for `compose()`, `uniqueId` / `idRef`, nested `component` nodes, and match outcomes.

### Dual-driver interactivity

Parity means **structure, classes, and static ARIA/ids** — not Alpine as the Gutenberg state machine.

| Surface | Owns interaction |
|---------|------------------|
| Frontend (Blade) | Alpine modules wired by `compose()` `x-*` bindings |
| Editor canvas | React, block attributes, and block context |

If a value is both Alpine runtime state and a block attribute or context value (active tab, open accordion item, etc.), the editor must drive it from React/context only. Do not sync Alpine from Gutenberg (`Alpine.$data`, mirroring clicks into `x-data`, etc.) — that does not scale.

Recipe for interactive organizers in a host theme:

1. Persist editor-visible state in block attributes and/or parent context.
2. Drive visibility and ARIA from those values in `editor.jsx` (`blockProps`, editor CSS, or React props).
3. Wire controls with React `onClick` or event delegation — never Alpine.
4. Leave full Alpine bindings in the PHP `compose()` schema for the published frontend.

**Host rule:** never import `@wordpress/*` into the editor iframe bootstrap. Register WordPress-dependent helpers (icon resolvers, etc.) from the parent editor bundle so the canvas script stays Alpine/DOM-only.

### Alpine in the canvas

By default (`sprout.editor.alpine` = `suppress`), attributes matching `^x-` or Alpine bind shorthand `^:[a-zA-Z]` are omitted before `createElement`. Static `id`, `aria-*`, classes, and styles remain. This is the recommended setting for interactive organizers: React owns toggles; Alpine stays frontend-only.

Set `editor.alpine` to `emit` only when you need Alpine directives on the canvas for **non-conflicting** preview (no React-owned controls on the same nodes). `emit` is incompatible with React-owned toggles on the same triggers — both will fight for state.

```php
// Theme: filter (opt-in preview only — not for React-driven organizers)
add_filter('sprout/editor/config', function (array $config): array {
    $config['editor']['alpine'] = 'emit';
    return $config;
});

// Or env / published config:
// SPROUT_EDITOR_ALPINE=emit
```

### Icons and nested `component` nodes

Blade Icons are PHP-only. Schema nodes serialize nested components as `"component": { "ref", "from", "map", "class", "props" }` (from `->component('x')->from()->map()->class()->props()->end()`). In the editor those nodes resolve through a host-supplied icon resolver:

```js
window.sprout.registerIconResolver(({ name, componentRef, className, props }) => {
    // return a React element for `name`, or null
});
```

The resolver callback still receives `componentRef` as the resolved `component.ref` string for host convenience. Resolution order: registered Sprout component → icon resolver → labelled debug placeholder (or nothing in production). Missing mapped values render nothing, matching Blade.
