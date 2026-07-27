# Installation

Four steps. Laravel gets Blade only; Sage / Acorn also gets the Gutenberg editor.

## 1. Require the package

```bash
composer require adamalexandersson/parity
```

## 2. Publish config (optional)

Root config is optional — package defaults render correctly without it.

```bash
# Laravel
php artisan vendor:publish --tag=parity

# Sage / Acorn
wp acorn vendor:publish --tag=parity
```

Shared class maps are additive and separate:

```bash
wp acorn vendor:publish --tag=parity-presets
# or: php artisan vendor:publish --tag=parity-presets
```

## 3. Wire Vite (WordPress / Sage only)

One plugin line replaces manual aliases and generated export files:

```js
import parity from './vendor/adamalexandersson/parity/vite.js';

export default defineConfig({
    plugins: [
        parity(),
        // ...
    ],
});
```

Regenerate the committed manifest before `dev` / `build` (needs a booted host):

```json
{
  "scripts": {
    "parity:manifest": "wp acorn parity:manifest",
    "predev": "npm run parity:manifest",
    "prebuild": "npm run parity:manifest"
  }
}
```

Import components as `import { Card } from '@parity/components'`. The plugin serves that module from `resources/js/parity/manifest.json` and writes ambient types beside it.

## 4. Write components

Create classes under `app/View/Components` that extend `Parity\View\Component` (or use `ComposesMarkup`) and implement `compose()`. Register host-specific transforms in your own service provider. On WordPress, depend on the `parity` script handle when enqueueing the block editor bundle.

That is the full install. See [editor.md](editor.md) for canvas helpers (`@parity/canvas`), Alpine policy, and icon resolvers.
