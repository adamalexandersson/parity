# Sprout editor integration

Gutenberg integration is **WordPress-only**. Plain Laravel applications use Sprout for Blade/schema rendering; they do not enqueue `dist/sprout.js`.

## Globals

| Global | Owner | Contents |
|--------|-------|----------|
| `window.sprout` | Sprout package | Runtime API + `config` (schemas, `common`, `tokens`, `classes`, `debug`) |
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

`createComponent` maps HTML attribute names for React (`class` → `className`, `for` → `htmlFor`, SVG camelCase) and coerces boolean attributes. When `window.sprout.config.debug` is true, schema/render errors surface as an in-block alert instead of failing silently. See [`docs/schema-v1.md`](schema-v1.md) for `uniqueId` / `idRef` and match outcomes.

### Alpine in the canvas

By default (`sprout.editor.alpine` = `suppress`), attributes matching `^x-` or Alpine bind shorthand `^:[a-z]` are omitted before `createElement`. Static `id`, `aria-*`, classes, and styles remain.

Set `editor.alpine` to `emit` when the theme loads Alpine inside the block editor canvas (iframe). Then schema `x-data` / `x-init` / `x-show` / `x-on:*` / `x-bind:*` reach the DOM and Alpine’s MutationObserver initializes them — the same modules as the frontend.

```php
// Theme: filter (recommended when Alpine is already in editor-iframe.js)
add_filter('sprout/editor/config', function (array $config): array {
    $config['editor']['alpine'] = 'emit';
    return $config;
});

// Or env / published config:
// SPROUT_EDITOR_ALPINE=emit
```

Known `componentRef` icons such as `heroicon-o-chevron-down` still render as SVG placeholders in the canvas (Blade Icons are PHP-only).
