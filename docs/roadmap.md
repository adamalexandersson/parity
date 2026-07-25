# Sprout roadmap

Working plan toward a production-ready 1.0 on Packagist, and beyond.

Sprout's single promise is that **a component defined once in PHP renders identically on the front end (Blade) and in the Gutenberg editor (React)**. Every item below is justified by either protecting that promise, removing a reason to work around it, or making the package trustworthy enough for others to adopt.

- [Current state](#current-state)
- [Guiding principles](#guiding-principles)
- [Decisions](#decisions)
- [Phase 0 — Correctness and foundations](#phase-0--correctness-and-foundations)
- [Phase 1 — Laravel-first architecture](#phase-1--laravel-first-architecture)
- [Phase 2 — Schema completeness](#phase-2--schema-completeness)
- [Phase 3 — Behavior and interactivity](#phase-3--behavior-and-interactivity)
- [Phase 4 — Release engineering](#phase-4--release-engineering)
- [Phase 5 — BEM and non-Tailwind support](#phase-5--bem-and-non-tailwind-support)
- [Phase 6 — Migration guide](#phase-6--migration-guide)
- [Reference theme track (Sleak)](#reference-theme-track-sleak)
- [Versioning policy](#versioning-policy)
- [Known bugs](#known-bugs)

---

## Current state

| Area | Status |
|------|--------|
| Schema builder (`Component`, `Node`, builders) | Working, covers classes, matches, attributes, styles, slots, mapped components |
| PHP renderer (`SchemaRenderer`, `structure.blade.php`) | Working, missing void elements |
| Editor runtime (`dist/sprout.js`) | Working, missing `any`/`all` conditions and void elements |
| Default shell fallback | Done, theme Blade override optional |
| `Sprout\View\Component` | Framework-agnostic already, no WordPress coupling |
| Parity tests | Coverage-enforced from JSON Schema enums |
| JS unit tests | Vitest |
| Static analysis | PHPStan level 6 + baseline |
| CI matrix | PHP 8.2–8.4 × Laravel 11–12 |
| Config publishing | Standard `vendor:publish` tags |
| Editor globals | `window.sprout.config` (package) / `window.sleak` (theme) |
| Editor component imports | Generated file per theme, lazy binding, silent fallback in production / loud when `debug` |
| WordPress coupling | Localized to `EditorAssets`, provider bootstrap, two console commands, two support classes |
| Packagist release | Not yet published |

---

## Guiding principles

1. **Parity is the product.** Any feature that can render differently in Blade and the editor is not done until both runtimes agree and a test proves it.
2. **No escape hatches for basic HTML.** If a developer has to write a theme Blade override or a hand-written React component to express ordinary markup, that is a schema gap, not a workaround.
3. **The schema is a public contract.** Anything that changes how existing schemas serialize or render is a breaking change after 1.0. Seams for future features go in before 1.0, even when the feature itself ships later.
4. **Fail at build time, not in the browser.** Malformed schemas should break `sprout:doctor` in CI, not render silently wrong in Gutenberg.
5. **Laravel-native.** A developer familiar with Laravel packages should find nothing surprising in structure, naming, testing, or tooling.
6. **Laravel first, WordPress adapted.** The schema and render layers know nothing about WordPress. WordPress is one supported host, not the foundation.

---

## Decisions

Architectural decisions with their rationale and consequences. All open decisions are resolved.

### D1. Does Sprout require WordPress? — Resolved: Laravel-first, WordPress adapted

Sprout targets Laravel with Blade as its foundation. WordPress integration moves behind an adapter, and `roots/acorn` becomes a Composer `suggest` rather than a hard requirement.

**Rationale.** The primary use case remains Sage-based WordPress sites, where the value is Blade/Gutenberg parity. But nothing in the schema, builders, renderer, or `Sprout\View\Component` needs WordPress — that layer is already clean. Decoupling widens the audience to any Laravel + Blade project, makes most of the package unit-testable without WordPress shims, and is far cheaper before 1.0 than after.

**Consequences.** See [Phase 1](#phase-1--laravel-first-architecture). The audit shows coupling is confined to `EditorAssets`, the provider bootstrap, `SafelistCommand`, `GenerateEditorExportsCommand`, `TransformRegistry`, and `ClassFactory` — a tractable surface.

### D2. Package name and brand — Resolved: `adamalexandersson/sprout`, namespace `Sprout\`

Locked. `sprout/sprout` is taken on Packagist by an unrelated Laravel multitenancy package, so the vendor-prefixed name stands. The PHP namespace stays `Sprout\`, and the editor runtime global stays `window.sprout`.

**Consequences.** No rename after 1.0. Confirm the name in `composer.json` during Phase 0 hygiene.

### D3. Pest or PHPUnit — Resolved: Pest

**Consequences.** Convert the existing 12 test files before expanding coverage in Phase 0, so the new parity, doctor, and adapter tests are written once rather than written in PHPUnit and then migrated.

### D4. Where do BEM element and modifier calls live in the schema? — Resolved: `classRules` with a mode flag

`->element()` and `->modifier()` serialize as entries in the existing `classRules` array carrying a `mode`, following the precedent already set by `apply()` with `mode: 'token'`:

```json
{ "mode": "element", "element": "header", "condition": null }
{ "mode": "modifier", "modifier": "type", "condition": null }
```

**Rationale.** No new top-level schema keys means the change is purely additive. Existing runtimes that ignore unknown modes degrade gracefully, and both renderers already branch on `mode` inside their class-rule loop, so the implementation slots into an existing seam.

**Consequences.** No schema major bump needed for BEM. Reserve and document the `element` and `modifier` modes in `schema-v1.md` during Phase 0 so 1.0 schemas are forward-compatible. Implement in [Phase 5](#phase-5--bem-and-non-tailwind-support).

### D5. How should blocks import Sprout components? — Resolved: committed manifest plus generated typed module

Keep code generation, but split it into a data step and a code step. PHP emits a committed `manifest.json`; the module and its type declarations are generated from that manifest without needing PHP.

```
sprout:manifest  ->  resources/js/sprout/manifest.json    (committed, data only)
                 ->  components.js + components.d.ts      (generated from manifest)
```

**Why runtime indirection is unavoidable.** `dist/sprout.js` is built as a single IIFE assigning `window.sprout`, with WordPress externals mapped to `wp.element` and friends. There must be exactly one registry and one React instance shared between the parent editor and every block bundle, and schema config arrives from PHP at runtime rather than build time. So `import { Card } from '@sprout/components'` bundles nothing — it is a lookup into a runtime registry. Named imports are ergonomics over that registry, not module resolution, and every option resolves through the same global.

**Why not a companion npm package.** Publishing `@adamalexandersson/sprout` to npm is the more idiomatic JS answer, but it puts the same schema contract behind two package managers with two version numbers. An npm runtime at 1.2 against Composer schemas at 1.1 is exactly the drift that is expensive to diagnose. The Composer package stays the single source of truth.

**Why not a Vite virtual module yet.** A plugin serving `virtual:sprout/components` removes the on-disk artifact entirely and is genuinely cleaner, but it needs the component list, which lives in PHP schemas — making the JS build depend on a bootable WordPress. Painful in CI and for frontend-only work. Splitting out a committed manifest gets the same benefit without that dependency, and leaves the plugin available later as optional polish consuming the same manifest.

**Why codegen is the right shape.** Generating a typed surface over a runtime that cannot be statically analyzed is the same pattern as Prisma's client or GraphQL Codegen. A committed manifest is also reviewable: adding a component shows up in a pull request as an intentional diff rather than a regenerated blob, and `sprout:doctor` can fail CI when the manifest drifts from the discovered schemas.

**Consequences.** See [Phase 1](#phase-1--laravel-first-architecture) for the manifest split and types, and [Phase 0](#phase-0--correctness-and-foundations) for the eager-binding and silent-fallback fixes. Two rules follow from this decision:

- **Generated artifacts carry data, not logic.** Resolution logic lives in the package so fixes ship via `composer update` rather than requiring every theme to regenerate.
- **Unknown components fail loudly outside production.** Returning `null` silently is the same class of defect as rendering silently wrong markup.

---

## Phase 0 — Correctness and foundations

Blockers for 1.0. Nothing here is a feature; it is all things that must be true before the package can be recommended to anyone.

### Convert the test suite to Pest

Do this first so every test written later in Phase 0 is written once (D3).

- [x] Add `pestphp/pest` and `pestphp/pest-plugin-laravel`
- [x] Convert the existing 12 test files
- [x] Keep Testbench for the tests that genuinely need an application container
- [x] Update `composer test` and CI

### Fix the four known bugs

- [x] **Void elements.** `structure.blade.php` always emits a closing tag, producing `<img></img>`. `createComponent.jsx` calls `createElement(tag, attrs, children)` which throws for void elements in React. Add a shared void-element list (`area`, `base`, `br`, `col`, `embed`, `hr`, `img`, `input`, `link`, `meta`, `param`, `source`, `track`, `wbr`), honored by both renderers, with parity fixtures.
- [x] **`any` / `all` conditions.** Implemented in `EvaluatesConditions.php` and documented in `schema-v1.md`, but absent from the JS `evaluateCondition` switch, which falls through to `return false`. Implement in JS including nested `conditions` arrays, and add fixtures.
- [x] **Undeclared `blade-icons` dependency.** `structure.blade.php` calls `@svg()` but `blade-ui-kit/blade-icons` is not in `require`. Any consumer without it fatals when rendering a `mappedComponent` node. Either declare the dependency, or make the SVG path conditional and document it as optional.
- [x] **Eager export binding caches failures permanently.** Generated exports resolve at module-evaluation time, so a component missing from `window.sprout.config` freezes the null fallback into the `const` for the page's lifetime — a blank block with no diagnostic. Resolve lazily on first render instead.

### Make missing components fail loudly

Per D5. Independent of the import strategy, and the single largest DX improvement available in this area.

- [x] `getComponent` throws or renders a visible error placeholder outside production, instead of returning a silent `null` component
- [x] Include the requested component name and the list of registered names in the message
- [x] Keep the silent fallback in production so a schema mismatch degrades rather than white-screens the editor

### Make parity coverage enforced, not sampled

The `any`/`all` divergence existed undetected because no fixture used those operators. Sampling catches regressions in paths you thought to test; it does not catch features shipped without tests.

- [x] Derive the list of schema features (condition operators, casts, outcome types, class-rule modes, node helpers, slot kinds) from a single source of truth
- [x] Add a test that fails when a feature has no parity fixture
- [x] Expand fixtures until the coverage test passes

### Add JS unit tests

The editor runtime has no direct tests. It is exercised only indirectly by PHP tests shelling out to Node via `scripts/*.mjs`.

- [x] Add Vitest
- [x] Unit-test `schemaRenderer.js` (conditions, casts, matches, common maps, tokens)
- [x] Unit-test `slotResolver.js` and `slots.js`
- [x] Run JS tests in CI alongside PHP

### Turn `sprout:doctor` into a real validator

Currently checks two things: `schemaVersion` equality and a non-empty `name`.

- [x] Write a JSON Schema document describing a valid Sprout component schema
- [x] Validate every discovered schema against it in `sprout:doctor`
- [x] Reuse the same document for the parity coverage check and editor runtime assertions
- [x] Report component name, node path, and the specific rule violated
- [x] Non-zero exit on failure so it is usable as a CI gate

### Add the class-strategy seam

`ClassFactory` hard-wires `TailwindMerge::instance()->merge(...)`. This must become pluggable before 1.0, because changing class composition semantics afterward is either breaking or a permanent second code path. It is also the seam BEM lands on in Phase 5.

- [x] Extract a class-strategy contract, resolved from `config('sprout.classes.strategy')`
- [x] Ship Tailwind (tailwind-merge) as the default strategy, behavior unchanged
- [x] Add a passthrough strategy (concatenate and dedupe, no merge)
- [x] Mirror the strategy on the JS side so parity holds
- [ ] Move `gehrisandro/tailwind-merge-php` toward optional once the seam exists

### Reserve the BEM schema surface

Per D4, the serialization shape is decided now even though the feature ships in Phase 5.

- [x] Document `mode: 'element'` and `mode: 'modifier'` in `schema-v1.md` as reserved class-rule modes
- [x] Ensure both renderers skip unknown `mode` values gracefully rather than throwing

### CI, static analysis, and code standards

- [x] Matrix CI: PHP 8.2 / 8.3 / 8.4 across Laravel 11 / 12 (Laravel 13 deferred until Testbench/Acorn align; Phase 1 removes the hard Acorn require)
- [x] PHPStan (or Larastan) at a high level, in CI
- [x] Keep Pint in CI (already present)
- [x] Dependabot for Composer and GitHub Actions
- [x] Run JS build + JS tests + PHP tests in one workflow

### Repository hygiene

- [ ] Replace placeholder author email `adam@example.com` in `composer.json` (deferred for Phase 0)
- [x] Confirm package name `adamalexandersson/sprout` per D2
- [x] Remove `"minimum-stability": "dev"`
- [x] Delete leftover `configure.php` from the package skeleton
- [x] Add `CHANGELOG.md` (Keep a Changelog format)
- [x] Add `SECURITY.md` and issue templates
- [x] Review `.gitattributes` for `export-ignore` on tests, scripts, and dev config

### Security pass

- [x] Validate attribute **names** (not just values) before rendering; reject anything outside a safe character set
- [x] Audit the inline config injection in `EditorAssets` for `</script>` breakout (confirm `wp_json_encode` flags are sufficient)
- [x] Audit every `{!! !!}` in `structure.blade.php` and `shell.blade.php` and document why each is safe

---

## Phase 1 — Laravel-first architecture

Implements D1. The goal is that `composer require adamalexandersson/sprout` works in a plain Laravel application, with WordPress as an adapter that activates when WordPress is present.

Sequenced before schema completeness because it relocates code and dramatically simplifies testing — every later phase benefits from being able to test without WordPress shims.

### Fix unguarded WordPress calls

These fatal outside WordPress today. Latent while WordPress is required; real bugs the moment it is not.

- [x] `src/Console/SafelistCommand.php` — resolved via `Host::path()`
- [x] `src/Registries/TransformRegistry.php` — resolved via `Host::escUrl()`
- [x] `src/Console/GenerateEditorExportsCommand.php` — reads manifest; host filters on `sprout:manifest`
- [x] Audit for any remaining unguarded calls after the adapter lands

### Extract the host adapter

- [x] Define a host contract covering the operations Sprout needs from its environment: escaping, filter/event dispatch, asset URL and path resolution, and JSON encoding
- [x] Implement a Laravel host (default): Laravel escaping, in-process filter bag, `base_path` / `asset` resolution
- [x] Implement a WordPress host: `esc_attr` / `esc_url`, `apply_filters`, theme vendor path resolution, `wp_json_encode`
- [x] Resolve the host from the container, auto-detected and overridable via config
- [x] Replace direct WordPress calls in `ClassFactory`, `TransformRegistry`, and the console commands with host calls

### Split the service providers

- [x] Core provider: registries, renderer, config merge, view namespace, commands, component discovery — no WordPress
- [x] WordPress provider: `EditorAssets` registration, `init` hook for discovery, block editor filters
- [x] Core provider registers the WordPress provider only when WordPress is detected
- [x] Keep the Acorn `extra.acorn.providers` entry working unchanged for Sage themes

### Move `EditorAssets` behind the adapter

`EditorAssets` is the heaviest coupling: `wp_register_script`, `wp_enqueue_script`, `wp_add_inline_script`, `wp_json_encode`, `get_theme_file_uri`, `get_theme_file_path`, `plugins_url`, and two `apply_filters` calls.

- [x] Extract config building (`buildConfig`) into a host-agnostic class — it is pure aside from the filter
- [x] Keep script registration and iframe injection in the WordPress adapter
- [x] Decide how a plain Laravel app consumes the editor runtime, or document Gutenberg integration as WordPress-only

### Editor component module surface

Implements D5. Grouped here because it is the same concern as the rest of this phase — not requiring a WordPress bootstrap for work that does not need one — and because `GenerateEditorExportsCommand` is already being touched for its unguarded `apply_filters` call.

**Split the data step from the code step:**

- [x] Add `sprout:manifest`, writing a committed `manifest.json` containing component slugs and prop metadata
- [x] Include each component's constructor signature (names, types, defaults) reflected from the PHP class
- [x] Rewrite `sprout:generate-editor-exports` to read the manifest rather than booting discovery, so the JS build needs no WordPress
- [x] Keep the manifest path configurable alongside `sprout.editor.exports_path`

**Make generated output dumb:**

- [x] Move the `getComponent` implementation out of the generated file and into the package, imported by the generated module
- [x] Generated module contains only the name-to-export mapping
- [x] Verify that regenerating in an existing theme produces no logic changes, only the component list

**Generate types:**

- [x] Emit `components.d.ts` from the manifest so each component carries real prop types
- [x] Wrong or misspelled props fail at build time instead of rendering subtly wrong markup in Gutenberg
- [x] Document the setup in `docs/editor.md`

**Guard against drift:**

- [x] `sprout:doctor` compares the manifest against discovered schemas and fails when they diverge
- [x] Add a `predev` hook in the reference theme to match the existing `prebuild`

**Optional, post-1.0:**

- [ ] Vite plugin serving `virtual:sprout/components` from the same manifest, removing the on-disk generated module entirely

### Dependencies and metadata

- [x] Move `roots/acorn` from `require` to `suggest`
- [x] Confirm explicit Illuminate components (`support`, `console`, `view`, `filesystem`) as framework requirements
- [x] Resolve the `blade-icons` question from Phase 0 in a host-neutral way
- [x] Document supported environments in the README: Laravel, and Sage/Acorn via the WordPress adapter

### Testing benefit

- [x] Run the schema, renderer, and component test suites with no WordPress functions defined
- [x] Add a small WordPress-function test double for adapter tests
- [x] Confirm coverage of the schema and render layers no longer requires Testbench where it does not need a container

---

## Phase 2 — Schema completeness

The goal from the original list: no developer should need a workaround for ordinary HTML. Done when a representative set of real-world components can be expressed schema-only.

### Element and attribute coverage

- [x] Void elements (Phase 0 fix, verified here against real components)
- [x] Boolean attributes rendered correctly in both runtimes (`disabled`, `checked`, `required`, `readonly`, `multiple`, `selected`, `open`, `hidden`, `controls`, `autoplay`, `muted`, `loop`, `playsinline`, `defer`, `async`, `novalidate`, `reversed`, `itemscope`)
- [x] React attribute name mapping table (`class` → `className`, `for` → `htmlFor`) with `data-*` and `aria-*` passed through verbatim
- [x] SVG support: correct namespace handling and camelCase attributes (`viewBox`, `strokeWidth`, `clipPath`) in the editor
- [x] `srcset` / `sizes` / `loading` / `decoding` on images
- [x] Form elements end to end: `label` + `for`, `input`, `select` with `option` children, `textarea`, `fieldset`, `legend`
- [x] Media elements with children: `picture` + `source`, `video` + `track`
- [x] Table structure: `table`, `thead`, `tbody`, `tr`, `th` with `scope`, `colgroup` + `col`
- [x] `microdata` / `itemprop` attributes (Sleak's FAQ schema already needs this)

### Unique IDs and accessibility primitives

There is no ID generation primitive today, so accessibility relationships that need stable unique IDs are impossible to express. Sleak threads `accordionId` through by hand.

- [x] `->uniqueId('panel')` primitive producing a stable per-instance ID
- [x] Reference generated IDs from sibling nodes for `for`, `aria-controls`, `aria-labelledby`, `aria-describedby`
- [x] Deterministic ID generation so Blade and editor output are comparable in parity tests
- [x] Document the accessibility patterns this unlocks

### Condition and match expressiveness

- [x] `any` / `all` in both runtimes (Phase 0 fix)
- [x] Nested condition groups
- [x] Consider `in` / `notIn`, `gt` / `gte` / `lt` / `lte`, `contains`, `empty` / `notEmpty`
- [x] Document every operator with a parity fixture (enforced by the Phase 0 coverage test)

### Error messages and DX

- [x] Throw clear exceptions naming the component and node path when a schema is invalid
- [x] Replace silent fallbacks with loud failures in local and non-production environments
- [x] Make the editor runtime surface schema errors in the block, not just the console

---

## Phase 3 — Behavior and interactivity

**This is the phase that pays off the `window.sleak` debt.** Schemas today describe structure, classes, attributes, and styles, but not behavior. Any interactive component therefore falls out of Sprout entirely into a hand-written React component plus a parallel config file. That is the entire reason a second editor global exists.

- [x] Express Alpine bindings in the schema (`x-data`, `x-init`, `x-show`, `x-on:*`, `x-bind:*`)
- [x] Decide how Alpine directives behave in the editor preview (default suppress; reference theme emits + boots Alpine in the canvas)
- [x] Combine with Phase 2 unique IDs so accordions and tabs can wire their own ARIA
- [x] Port Accordion, AccordionItem, Tabs, TabItem, and Buttons to schema-only components
- [x] Delete the corresponding hand-written React in the reference theme
- [x] Reduce `window.sleak` to icons and AJAX config only
- [x] Re-document the escape hatch as genuinely exceptional rather than load-bearing

Success criterion: the legacy `components` key in `window.sleak` is empty.

---

## Phase 4 — Release engineering

Ends with a `1.0.0` tag on Packagist.

### Distribution

- [ ] Add `pixelfear/composer-dist-plugin` so built artifacts ship without committing `dist/`
- [ ] Stop committing `dist/sprout.js`; add to `.gitignore` and `export-ignore`
- [ ] **Verify** the editor asset resolver still finds `vendor/adamalexandersson/sprout/dist/sprout.js` after the dist swap — the WordPress adapter resolves that path directly
- [ ] Tag-triggered workflow: build, test, create release, attach dist artifact
- [ ] Packagist webhook so tags sync automatically
- [ ] Switch the editor script version from `filemtime` to a content hash, so cache busting survives deploys that do not preserve mtimes

### Documentation

Current docs are `README.md`, `docs/schema-v1.md`, `docs/contributing.md`, and this roadmap. For a public 1.0 that should become a navigable set.

- [ ] `docs/installation.md` — Laravel and Sage/Acorn paths
- [ ] `docs/components.md` — authoring guide
- [ ] `docs/schema-v1.md` — full reference (exists, expand as Phase 2 lands)
- [ ] `docs/editor.md` — Gutenberg integration, globals, the manifest, generated exports and types
- [ ] `docs/transforms.md`
- [ ] `docs/classes.md` — strategies, Tailwind, BEM
- [ ] `docs/hosts.md` — the Laravel and WordPress adapters
- [ ] `docs/testing.md` — how parity works, how to add fixtures
- [ ] `docs/upgrading.md`
- [ ] README trimmed to overview plus links

### Pre-release checklist

- [ ] Phases 0 through 3 complete
- [ ] `sprout:doctor` green on the reference theme
- [ ] Parity coverage test green
- [ ] Matrix CI green
- [ ] Package installs and renders in a bare Laravel app with no WordPress present
- [ ] `CHANGELOG.md` written for 1.0

---

## Phase 5 — BEM and non-Tailwind support

Ships as 1.1, on the class-strategy seam from Phase 0 and the `classRules` mode shape from D4. Additive, no breaking changes, no schema major.

- [ ] `->element('header')` producing `block__element`, serialized as `mode: 'element'`
- [ ] `->modifier('type', 'list')` producing `block--modifier`, serialized as `mode: 'modifier'`, driven by prop values
- [ ] Block name derived from the component name, overridable
- [ ] `config('sprout.bem')` for separators, defaulting to `__` and `--`
- [ ] Configurable modifier value formatting (kebab-case, raw)
- [ ] BEM as a selectable class strategy, composable with literal classes
- [ ] Mirror the generator in JS so parity holds
- [ ] Parity fixtures for every BEM feature
- [ ] `docs/classes.md` BEM section with a full worked example

---

## Phase 6 — Migration guide

For themes on the pre-Sprout DSL config. Written **while** migrating the reference theme, frozen once the schema is frozen at 1.0 — drafting it against a moving schema means rewriting it.

- [ ] `docs/migrating-from-dsl.md`, written to be usable as AI agent context
- [ ] Old-pattern to new-pattern mapping table
- [ ] Worked examples: a simple component, a component with matches, a component with slots, an interactive component
- [ ] Common pitfalls, especially boolean and enum normalization
- [ ] `sprout:doctor --legacy` mode that scans a theme for old DSL config and reports specifically what to change

The guide tells an agent *how*; the command tells it *where*. Prose alone leaves the agent pattern-matching.

---

## Reference theme track (Sleak)

Sleak is the proving ground. Anything Sleak needs a workaround for is a package gap.

- [ ] Migrate remaining components to schema-only as Phase 2 lands
- [x] Retire legacy React components in Phase 3
- [x] Shrink `window.sleak` to icons plus AJAX config
- [x] Boot Alpine in the Gutenberg canvas (`editor.alpine` = `emit` via `sprout/editor/config`)
- [ ] Keep `wp acorn sprout:doctor` green in the theme's own CI
- [ ] Track every workaround as a package issue rather than fixing it locally
- [ ] Consider extracting a starter theme once 1.0 is out

---

## Versioning policy

Two versions travel together and the relationship needs stating explicitly, because users will ask.

**Package version** follows semver on the PHP and JS API: builder methods, config keys, service contracts, host adapters, published files.

**Schema version** (`Sprout\Schema\Version::CURRENT`, currently `1.0`) versions the serialized schema contract consumed by the editor runtime.

- A schema **minor** bump means additive keys or new `mode` values. Older runtimes ignore them; newer runtimes read older schemas.
- A schema **major** bump means a breaking serialization change and requires a package major.
- A package major does **not** necessarily imply a schema major.

Per D4, BEM support is additive and does not bump the schema major.

- [ ] Document this in `docs/upgrading.md`
- [ ] Runtime warns on schema major mismatch, tolerates minor differences
- [ ] Every schema change noted in `CHANGELOG.md` with its schema-version impact

---

## Known bugs

Phase 0 bugs 1–4 and Phase 1 bug 5 (unguarded WordPress calls) are fixed.

### 1. Void elements produce invalid HTML and crash the editor

`resources/views/structure.blade.php` emits a closing tag unconditionally:

```php
@if (! $isFragment)
    <{{ $element['tag'] ?? 'div' }}{!! $attrString !!}>
@endif
```

An `img` node renders `<img></img>` on the front end. In the editor, `createElement('img', attrs, children)` throws, because React forbids children on void elements.

**Impact:** any component needing `img`, `input`, `br`, `hr`, or `source` requires a theme Blade override — the exact workaround Sprout exists to remove.

### 2. `any` / `all` conditions diverge between runtimes

Implemented in `src/Concerns/EvaluatesConditions.php`:

```php
'any' => $this->evaluateAnyCondition($condition),
'all' => $this->evaluateAllCondition($condition),
```

Absent from the `evaluateCondition` switch in `resources/js/render/schemaRenderer.js`, which falls through to `default: return false`. Nested `conditions` arrays are not read at all on the JS side.

**Impact:** a schema using documented operators renders differently in Blade and Gutenberg, breaking the core guarantee. Undetected because no parity fixture exercised them.

### 3. `blade-icons` is used but not declared

`resources/views/structure.blade.php` calls `@svg($mappedValue)` when rendering a `mappedComponent` node, but `blade-ui-kit/blade-icons` appears nowhere in `composer.json`.

**Impact:** any consumer without blade-icons independently installed fatals on mapped-component rendering. Sleak only works because it happens to have the package. Either declare it, or make the SVG branch conditional.

### 4. Eager export binding caches failures permanently

Generated editor exports resolve at module-evaluation time:

```js
export const Card = getComponent('card');
```

If `window.sprout.config` does not contain `card` at that moment — stale `sprout:cache`, discovery skipped, component renamed, generated module out of date — `getComponent` returns the null fallback and that fallback is frozen into the `const` for the page's lifetime.

Compounding it, the fallback returns `null` with no warning, so the result is a blank block with nothing in the console.

**Impact:** a recoverable condition becomes permanent for the session, and the most common development-time mistake produces no diagnostic at all. Fixed by lazy resolution plus loud failure in Phase 0; the underlying staleness is prevented by the manifest drift check in [Phase 1](#phase-1--laravel-first-architecture).

### 5. Unguarded WordPress calls (latent) — fixed in Phase 1

Resolved via the `Host` contract (`LaravelHost` / `WordPressHost`). Safelist and exports resolve paths through `Host::path()`; URL escaping and filters go through the host.
