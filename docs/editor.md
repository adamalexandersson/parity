# Parity editor integration

Gutenberg integration is **WordPress-only**. Plain Laravel applications use Parity for Blade/schema rendering; they do not enqueue `dist/parity.js`.

Install steps live in [installation.md](installation.md). This document covers the editor runtime once Vite is wired with `parity()`.

## Globals

| Global | Owner | Contents |
|--------|-------|----------|
| `window.parity` | Parity package | Runtime API + `config` (schemas, `presets`, `tokens`, `classes`, `debug`) |
| `window.sleak` | Theme (optional) | Icons and AJAX helpers only — not part of Parity. Interactive UI belongs on `@parity/components`. |

Config is injected with `wp_add_inline_script` before the Parity bundle when the WordPress host is active (`SCRIPT_DEBUG` maps to `config.debug`). The package also injects the same config into the Gutenberg iframe assets; `@parity/canvas` bridges from `window.parent` as a fallback.

## Vite plugin

```js
import parity from './vendor/adamalexandersson/parity/vite.js';

export default defineConfig({
    plugins: [parity()],
});
```

The plugin:

- Resolves `@parity/runtime` to the package's `createExport` helper
- Resolves `@parity/components` / `virtual:parity/components` from the committed manifest
- Resolves `@parity/canvas` to iframe bootstrap helpers
- Writes ambient `components.d.ts` (gitignored) for prop checking
- Warns when the manifest is missing, unparseable, or older than component PHP files

Options: `manifest`, `types` (path or `false`), `componentsDir`.

## Manifest workflow

```bash
wp acorn parity:manifest
```

Theme `package.json`:

```json
{
  "scripts": {
    "parity:manifest": "wp acorn parity:manifest",
    "predev": "npm run parity:manifest",
    "prebuild": "npm run parity:manifest"
  }
}
```

Default path: `editor.manifest_path` → `resources/js/parity/manifest.json`.

`parity:doctor` fails when the manifest drifts from discovered schemas.

Generated imports are thin:

```js
import { createExport } from '@parity/runtime';
export const Card = createExport('card');
```

`createExport` resolves lazily through `window.parity.getComponent` on render (never at module evaluation time).

## Canvas helpers

```js
import { bridgeCanvasConfig, bootAlpine } from '@parity/canvas';

bridgeCanvasConfig();

bootAlpine(Alpine, {
    data: { accordion, tabs },
    plugins: [focus, collapse],
});
```

- `bridgeCanvasConfig()` — inside the iframe, copies `window.parent.parity.config` when needed
- `bootAlpine(Alpine, options)` — opt-in; Alpine is injected by the theme so the package stays free of an `alpinejs` dependency

Keep canvas modules free of `@wordpress/*` imports. Register WordPress-dependent helpers (icon resolvers, etc.) from the parent editor bundle.

The only required enqueue step for a theme is depending on the `parity` script handle when loading the block editor bundle.

## TypeScript

The plugin writes an ambient `declare module '@parity/components'` file on build start. No `jsconfig.json` / `tsconfig.json` is required for editors to pick up prop types.

## Attributes in the editor

`createComponent` maps HTML attribute names for React (`class` → `className`, `for` → `htmlFor`, SVG camelCase) and coerces boolean attributes. When `window.parity.config.debug` is true, schema/render errors surface as an in-block alert instead of failing silently. See [`docs/schema-v1.md`](schema-v1.md) for `compose()`, `uniqueId` / `idRef`, nested `component` nodes, and match outcomes.

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

### Alpine in the canvas

By default (`parity.editor.alpine` = `suppress`), attributes matching `^x-` or Alpine bind shorthand `^:[a-zA-Z]` are omitted before `createElement`. Static `id`, `aria-*`, classes, and styles remain. This is the recommended setting for interactive organizers: React owns toggles; Alpine stays frontend-only.

Set `editor.alpine` to `emit` only when you need Alpine directives on the canvas for **non-conflicting** preview (no React-owned controls on the same nodes). `emit` is incompatible with React-owned toggles on the same triggers — both will fight for state.

```php
// Theme: filter (opt-in preview only — not for React-driven organizers)
add_filter('parity/editor/config', function (array $config): array {
    $config['editor']['alpine'] = 'emit';
    return $config;
});

// Or env / published config:
// PARITY_EDITOR_ALPINE=emit
```

### Icons and nested `component` nodes

Blade Icons are PHP-only. Schema nodes serialize nested components as `"component": { "ref", "from", "map", "class", "props" }` (from `->component('x')->from()->map()->class()->props()->end()`). In the editor those nodes resolve through a host-supplied icon resolver:

```js
window.parity.registerIconResolver(({ name, ref, className, props }) => {
    // return a React element for `name`, or null
});
```

The resolver callback receives `ref` as the resolved `component.ref` string. Resolution order: registered Parity component → icon resolver → labelled debug placeholder (or nothing in production). Missing mapped values render nothing, matching Blade.
