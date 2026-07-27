# Parity roadmap

Working plan toward a production-ready 1.0 on Packagist, and beyond.

The single promise is that **a component defined once in PHP renders identically on the front end (Blade) and in the Gutenberg editor (React)**. Every item below is justified by either protecting that promise, removing a reason to work around it, or making the package trustworthy enough for others to adopt.

> **Naming.** The package ships today as `adamalexandersson/sprout` with the `Sprout\` namespace. It is being renamed to **Parity** before 1.0 ([D2](#d2-package-name-and-brand--resolved-rename-to-parity), [Phase 3](#phase-3--rename-to-parity)). This document uses `Sprout` when describing code as it exists now and `Parity` when describing the target.

- [Shipped](#shipped)
- [Current state](#current-state)
- [Guiding principles](#guiding-principles)
- [Decisions](#decisions)
- [Phase 1 — Cleanup and hardening](#phase-1--cleanup-and-hardening)
- [Phase 2 — Authoring API and vocabulary](#phase-2--authoring-api-and-vocabulary)
- [Phase 3 — Rename to Parity](#phase-3--rename-to-parity)
- [Phase 4 — Zero-config install](#phase-4--zero-config-install)
- [Phase 5 — Release engineering](#phase-5--release-engineering)
- [Phase 6 — BEM and non-Tailwind support](#phase-6--bem-and-non-tailwind-support)
- [Phase 7 — Migration guide](#phase-7--migration-guide)
- [Reference theme track (Sleak)](#reference-theme-track-sleak)
- [Versioning policy](#versioning-policy)
- [Known issues](#known-issues)

---

## Shipped

Completed work, summarized. Full detail lives in `CHANGELOG.md`.

**Correctness and foundations.** Test suite converted to Pest. The four launch-blocking bugs fixed: void elements, `any`/`all` condition divergence, the undeclared `blade-icons` dependency, and eager export binding caching failures permanently. Missing components now fail loudly outside production. Parity coverage is enforced from the JSON Schema rather than sampled, so a feature without a fixture fails CI. `sprout:doctor` validates every discovered schema against `resources/schema/component.schema.json` and exits non-zero. Vitest added for the editor runtime. Matrix CI across PHP 8.2–8.4 and Laravel 11–12, with PHPStan level 6 and Pint. Class composition moved behind a pluggable strategy seam (`tailwind`, `passthrough`), mirrored in JS. BEM's `mode: 'element'` / `mode: 'modifier'` reserved in the schema. Security pass on attribute names, inline config injection, and every `{!! !!}` in the shipped views.

**Laravel-first architecture.** WordPress moved behind a `Host` contract with `LaravelHost` and `WordPressHost` implementations; the schema and render layers no longer reference WordPress at all. Providers split into a core provider and a WordPress provider that registers only when WordPress is detected. `roots/acorn` moved from `require` to `suggest`. Editor exports split into a data step and a code step: `sprout:manifest` writes a committed `manifest.json`, and `sprout:generate-editor-exports` builds `components.js` plus `components.d.ts` from it without booting WordPress. Resolution logic moved out of generated files and into the package. `sprout:doctor` fails on manifest drift.

**Schema completeness.** Void and boolean attributes, the React attribute-name mapping table, SVG namespacing, responsive image attributes, full form and media element coverage, table structure, and microdata. Unique-ID generation (`uniqueId`, `idRef`, `{name}` interpolation) with deterministic output so Blade and editor IDs are comparable in parity tests. Condition operators extended to `in`, `notIn`, `gt`, `gte`, `lt`, `lte`, `contains`, `empty`, `notEmpty`, plus nested groups — each with a parity fixture.

**Behavior and interactivity.** Alpine bindings expressible in the schema (`x-data`, `x-init`, `x-show`, `x-cloak`, `x-on:*`, `x-bind:*`), combined with unique IDs so accordions and tabs wire their own ARIA. `{name}` placeholders always interpolate (escape with `{{name}}`). The editor suppresses Alpine directives by default; `emit` remains an opt-in for non-conflicting preview only. **Dual-driver convention:** frontend interaction is Alpine; editor interaction is React / block attributes / context (parity is markup and classes, not Alpine as the editor state machine). Accordion, AccordionItem, Tabs, TabItem, and Buttons are schema-only, their hand-written React counterparts deleted, and `window.sleak` reduced to icons and AJAX config.

**Authoring API (Phase 2).** Vocabulary renamed around `compose()`, `prepare()`, `preset()`, `token()`, `->slot()` / `->slot('name')`, nested `->component()->from()->map()->…->end()`, and MatchBuilder `when`/`unless` vs node `visible`/`hidden`. Lazy boot via `ComposesMarkup` / `Composable` removes constructor forwarding. Serialized keys kept stable (`defaultSlot`, `namedSlots`, `matches[].preset`, nested `component`).

All five previously tracked bugs are closed. Open issues are listed under [Known issues](#known-issues).

---

## Current state

| Area | Status |
|------|--------|
| Schema builder (`Component`, `Node`, builders) | Feature-complete; Phase 2 vocabulary shipped |
| PHP renderer (`SchemaRenderer`, `structure.blade.php`) | Working |
| Editor runtime (`dist/sprout.js`) | Working, at parity with PHP |
| `Sprout\View\Component` | Thin abstract over `ComposesMarkup` + `Composable` |
| Host adapter | `LaravelHost` / `WordPressHost` behind a contract |
| Parity tests | Coverage-enforced from JSON Schema enums |
| JS unit tests | Vitest, in CI |
| Static analysis | PHPStan level 6 + baseline |
| CI matrix | PHP 8.2–8.4 × Laravel 11–12 |
| Editor globals | `window.sprout.config` (package) / `window.sleak` (theme icons + AJAX only) |
| Editor component imports | Committed manifest → generated module + types; lazy binding, loud failure outside production |
| Theme install surface | ~10 steps; target ≤4 in Phase 4 |
| Package name | `adamalexandersson/sprout`; renaming to Parity in Phase 3 |
| Packagist release | Not yet published |

---

## Guiding principles

1. **Parity is the product.** Any feature that can render differently in Blade and the editor is not done until both runtimes agree and a test proves it.
2. **No escape hatches for basic HTML.** If a developer has to write a theme Blade override or a hand-written React component to express ordinary markup, that is a schema gap, not a workaround.
3. **The schema is a public contract.** Anything that changes how existing schemas serialize or render is a breaking change after 1.0. Seams for future features go in before 1.0, even when the feature itself ships later.
4. **Fail at build time, not in the browser.** Malformed schemas should break `parity:doctor` in CI, not render silently wrong in Gutenberg.
5. **Laravel-native.** A developer familiar with Laravel packages should find nothing surprising in structure, naming, testing, or tooling.
6. **Laravel first, WordPress adapted.** The schema and render layers know nothing about WordPress. WordPress is one supported host, not the foundation.
7. **The authoring API is the interface most people will judge.** A method name that needs a docs lookup to understand is a defect. Prefer words a component author already knows over words invented for the implementation.
8. **It should just work.** Every step a consuming project must perform by hand is a step that can be done wrong, drift, or go stale. Configuration the package can derive, the package derives.

---

## Decisions

Architectural decisions with their rationale and consequences.

### D1. Does Parity require WordPress? — Resolved: Laravel-first, WordPress adapted

Parity targets Laravel with Blade as its foundation. WordPress integration lives behind a host adapter, and `roots/acorn` is a Composer `suggest` rather than a hard requirement.

**Rationale.** The primary use case remains Sage-based WordPress sites, where the value is Blade/Gutenberg parity. But nothing in the schema, builders, renderer, or the component base needs WordPress. Decoupling widens the audience to any Laravel + Blade project and makes most of the package unit-testable without WordPress shims.

**Status.** Implemented. See [Shipped](#shipped).

### D2. Package name and brand — Resolved: rename to Parity

Supersedes the earlier decision to keep `Sprout`. The package becomes:

| Surface | Value |
|---------|-------|
| Composer | `adamalexandersson/parity` |
| PHP namespace | `Parity\` |
| Facade / alias | `Parity` |
| Config file | `config/parity.php`, keys `parity.*` |
| Env vars | `PARITY_HOST`, `PARITY_CLASS_STRATEGY`, `PARITY_EDITOR_ALPINE` |
| Commands | `parity:make`, `parity:doctor`, `parity:manifest`, … |
| Editor global | `window.parity` |
| Editor bundle | `dist/parity.js`, script handle `parity` |
| Host filters | `parity/editor/config`, `parity/editor/export-names`, `parity/editor/script_url` |
| View namespace | `Parity::shell`, `Parity::structure` |
| Vite aliases | `@parity/runtime`, `@parity/components` |

**Rationale.** "Sprout" says nothing about what the package does; "Parity" names the single promise and is the word already used throughout the docs, tests, and this roadmap to describe the guarantee. The vendor-scoped name is kept: `parity/parity` is unclaimed but `icecave/parity` is an established PHP package with millions of installs, so an unscoped name would compete for search results with an unrelated comparison library.

**Consequences.** Mechanical but wide. No backwards-compatibility aliases — the package is unpublished, so it is a single cutover, sequenced after the API work so nothing is renamed twice. See [Phase 3](#phase-3--rename-to-parity).

### D3. Pest or PHPUnit — Resolved: Pest

**Status.** Implemented.

### D4. Where do BEM element and modifier calls live in the schema? — Resolved: `classRules` with a mode flag

`->element()` and `->modifier()` serialize as entries in the existing `classRules` array carrying a `mode`, following the precedent set by `token()` with `mode: 'token'`:

```json
{ "mode": "element", "element": "header", "condition": null }
{ "mode": "modifier", "modifier": "type", "condition": null }
```

**Rationale.** No new top-level schema keys means the change is purely additive. Runtimes that ignore unknown modes degrade gracefully, and both renderers already branch on `mode` inside their class-rule loop.

**Consequences.** No schema major bump needed for BEM. The modes are already reserved and documented. Implement in [Phase 6](#phase-6--bem-and-non-tailwind-support).

### D5. How should blocks import Parity components? — Resolved: committed manifest, now consumed by a Vite plugin

Code generation split into a data step and a code step: PHP emits a committed `manifest.json`, and the JS surface is derived from that manifest without needing PHP.

**Why runtime indirection is unavoidable.** The editor bundle is a single IIFE assigning `window.parity`, with WordPress externals mapped to `wp.element` and friends. There must be exactly one registry and one React instance shared between the parent editor and every block bundle, and schema config arrives from PHP at runtime rather than build time. So `import { Card } from '@parity/components'` bundles nothing — it is a lookup into a runtime registry. Named imports are ergonomics over that registry, not module resolution.

**Amended.** The original decision deferred a Vite plugin because it would need the component list from PHP, making the JS build depend on a bootable WordPress. Splitting out the committed manifest removed that dependency: the plugin reads `manifest.json` from disk. The virtual module therefore moves from "optional, post-1.0" into [Phase 4](#phase-4--zero-config-install), replacing the generated on-disk module.

**Why still not a companion npm package.** Publishing to npm puts the same schema contract behind two package managers with two version numbers. An npm runtime at 1.2 against Composer schemas at 1.1 is exactly the drift that is expensive to diagnose. The Composer package stays the single source of truth, and the Vite plugin ships inside it (D7).

**Consequences.** Two rules stand: generated artifacts carry data, not logic, so fixes ship via `composer update`; and unknown components fail loudly outside production.

### D6. Abstract base class or trait? — Resolved: trait as the real API, thin abstract for convenience

`Parity\Concerns\ComposesMarkup` holds the entire implementation. `Parity\View\Component` becomes a near-empty abstract that extends `Illuminate\View\Component` and uses the trait.

**Rationale.** Forcing a base class is the one thing that makes a Blade component package hard to adopt incrementally. A project with its own component base, or a single component that needs to extend something else, currently has no path in. A trait composes; a base class excludes. Keeping the thin abstract costs nothing and preserves the shorter `extends Component` for the common case.

**Consequences.** Component discovery must stop scanning for subclasses of the abstract and start resolving against a `Parity\Contracts\Composable` interface (or trait usage). The trait must not depend on constructor cooperation, which removes the `parent::__construct(...func_get_args())` boilerplate every component carries today — see [Phase 2](#phase-2--authoring-api-and-vocabulary).

### D7. How does a project wire up the JS side? — Resolved: first-party Vite plugin shipped inside the Composer package

The package ships a Vite plugin at `vendor/adamalexandersson/parity/vite.js`. A consuming project imports it by path and adds one line to `vite.config.js`; the plugin registers the aliases and serves the component module.

**Rationale.** Manual `resolve.alias` entries pointing into `vendor/` are the least defensible part of the current install: they hardcode a vendor path in application config, they break silently when the path changes, and their ordering matters. A plugin owns that resolution, which means it can be fixed in a `composer update` rather than in every consuming project. Shipping it inside the Composer package rather than on npm keeps D5's single-source-of-truth rule intact — the plugin can never be a version behind the schemas it serves, because it is the same install.

**Consequences.** See [Phase 4](#phase-4--zero-config-install).

### D8. How aggressive is the pre-1.0 API pass? — Resolved: full vocabulary rename, no deprecation aliases

The builder vocabulary, the `compose()` naming, and the trait migration land as one coordinated breaking change before 1.0.

**Rationale.** The schema is a public contract from 1.0 onward, and so is the authoring API. Terms like `holdsDefaultSlot`, `includeCommon`, `mappedComponent`, and `unlessProp` describe the implementation rather than the concept, and every one of them becomes permanent the day the package is published. Shipping aliases for a package with no public users adds two names for every concept in exchange for compatibility nobody needs.

**Consequences.** The reference theme migrates in the same change. [Phase 2](#phase-2--authoring-api-and-vocabulary) carries the full inventory.

---

## Phase 1 — Cleanup and hardening

Sequenced first so nothing dead gets carried through the rename or the API pass. The goal is that every public symbol in the package is reachable, tested, and does what its name says.

### Remove or wire up dead code

- [x] `ComponentRegistry` is registered in the container and exposed on the facade, but nothing reads or writes it — delete it, or wire it as the real Blade-to-editor name map and test it
- [x] `parity:cache` writes `Cache::forever('sprout.schemas', …)` but `ConfigCollector` never reads the cache, so the command has no effect — implement the read path or remove both `cache` and `clear`
- [x] Remove the reserved `icons` / `iconAjaxUrl` / `iconAjaxNonce` keys filtered out during discovery; they are vestiges of the pre-schema theme config era
- [x] Sweep `resources/js/` for unreferenced exports and support modules left over from earlier editor iterations
- [x] Audit test fixtures and cached artifacts for references to removed features

### Remove theme-specific content from the package

- [x] `resources/js/support/componentRefIcons.js` hardcodes a `chevron-down` SVG so mapped-component nodes render something in the editor — package code should not know about one theme's icon set
- [x] Replace it with an icon-resolution contract the host supplies, or render a neutral placeholder and document how a host registers real icons
- [x] Confirm no other package file encodes assumptions about the reference theme

### Reconcile overlapping surfaces

- [x] Three publish tags (`sprout`, `sprout-config`, `sprout-common`) ship overlapping content — reduce to one tag plus documented granular tags if genuinely needed
- [x] `blade-ui-kit/blade-icons` is optional but the failure mode when absent should be a clear exception, not a fatal
- [x] Move `gehrisandro/tailwind-merge-php` to optional now that the class-strategy seam exists (carried over from Phase 0)

### Documentation drift

- [x] `docs/security.md` documents `EditorAssets::encodeConfig()`; the real API is `EditorConfigBuilder::encode()`
- [x] Verify every code sample in `README.md`, `docs/schema-v1.md`, and `docs/editor.md` against the current API before the rename rewrites them all
- [x] Replace the placeholder author email `adam@example.com` in `composer.json` (carried over from Phase 0)

**Done when:** no public symbol is unreachable, no package file names a theme-specific asset, and every documented API exists.

---

## Phase 2 — Authoring API and vocabulary

Completed. Implements [D6](#d6-abstract-base-class-or-trait--resolved-trait-as-the-real-api-thin-abstract-for-convenience) and [D8](#d8-how-aggressive-is-the-pre-10-api-pass--resolved-full-vocabulary-rename-no-deprecation-aliases).

Two tests for every name below: could a component author guess what it does without reading the docs, and does it describe the concept rather than the mechanism?

### Composition entry point

- [x] Rename `schema()` to `compose()` — a component composes markup; the schema is the serialization format, which is an implementation detail the author should not have to name
- [x] Extract `Parity\Concerns\ComposesMarkup` holding the full implementation
- [x] Reduce `Parity\View\Component` to an abstract extending `Illuminate\View\Component` and using the trait
- [x] Add a `Parity\Contracts\Composable` interface declaring `compose(): array`
- [x] Switch component discovery from "subclass of the base" to "implements `Composable`"
- [x] Boot lazily from `render()` rather than the constructor, so `parent::__construct(...func_get_args())` is no longer required in every component
- [x] Document the boot lifecycle explicitly, since a trait makes it less obvious than a base class did

### Slot vocabulary

Five names currently express two ideas: `Component::slot()`, `Node::namedSlot()`, `holdsDefaultSlot()`, `holdsNamedSlot()`, and the serialized `defaultSlot` / `namedSlots`.

- [x] Collapse to a single node-level verb: `->slot()` marks the default slot holder, `->slot('name')` marks a named one
- [x] Derive the component-level `defaultSlot` path automatically from whichever node claims it, instead of declaring it twice
- [x] Keep the serialized schema keys stable where possible so the change is authoring-only

### Shared class maps and tokens

- [x] Rename `includeCommon('justify')` — "common" names where the config lives, not what the call does; `->preset('justify')` or `->shared('justify')` describes the concept
- [x] Rename the matching config key (`parity.common` → `parity.presets`) and the editor config key with it
- [x] Rename `apply('group', 'token')` to `->token('group', 'name')` so the method says what it adds

### Component nesting

- [x] Merge `component()` and `mappedComponent()` into one builder following the `attr()` shape: `->component('icon')->from('icon')->map([...])->end()`
- [x] Align the serialized keys (`componentRef`, `componentMapping`, `componentMappingKey`) with whatever the merged API is called

### Condition verbs

- [x] `MatchBuilder::onlyWhen()` / `unlessProp()` become `when()` / `unless()`, matching `Node` and `AttrBuilder`
- [x] Review `visible()` / `hidden()` against `when()` / `unless()` — four verbs for two concepts, distinguished only by whether they hide the node or the class
- [x] Confirm `ConditionBuilder`'s static operator set reads consistently

### Identifiers

- [x] Review `uniqueId()` / `idRef()` / `interpolateIds()` — three names for one feature
- [x] Consider making `{name}` interpolation always-on with a documented escape, removing `interpolateIds()` from the public API
- [x] Keep `Node::uniqueId()` only if it is meaningfully shorter than `->attr('id')->uniqueId()->end()`

### Builder ergonomics

- [x] Evaluate the `Component` / `Node` split — `Component extends Node` and adds four methods; one entry point may read better
- [x] Evaluate closure-based sub-builders (`->match('gap', fn ($m) => $m->case('1')->classes('gap-1'))`) as an alternative to `->end()` chains, which are the most error-prone part of the current API
- [x] Review `linkable()`, `fragment()`, and `richText()` against the same two tests
- [x] Write one representative component (a card, an accordion, a grid) in the proposed API before committing to it, and compare side by side with today's version

### Follow-through

- [x] Update the JSON Schema, both renderers, and every fixture
- [x] Migrate all 22 reference-theme components in the same change
- [x] Rewrite `docs/schema-v1.md` around the new vocabulary
- [x] Add a `parity:doctor` check that reports old method names with their replacements, so the migration is mechanical for anyone who tracked `dev-main`

**Done when:** a developer can write a non-trivial component from the docs' table of contents alone, and no public method name describes an internal mechanism.

---

## Phase 3 — Rename to Parity

Implements [D2](#d2-package-name-and-brand--resolved-rename-to-parity). Deliberately last among the breaking changes: by this point the API is final, so the rename is one mechanical, reviewable commit and nothing is renamed twice.

- [ ] Confirm `adamalexandersson/parity` is still unclaimed on Packagist immediately before the change
- [ ] `composer.json`: name, description, autoload, facade alias, `extra.laravel` and `extra.acorn` provider entries
- [ ] PHP namespace `Sprout\` → `Parity\` across `src/`, `tests/`, and `stubs/`
- [ ] Config: `config/sprout.php` → `config/parity.php`, all `config('sprout.*')` reads, all `PARITY_*` env vars
- [ ] Console command signatures and their references in scripts and docs
- [ ] Editor runtime: `window.sprout` → `window.parity`, `dist/sprout.js` → `dist/parity.js`, script handle, Vite build output
- [ ] Host filter names (`parity/editor/config`, `parity/editor/export-names`, `parity/editor/script_url`)
- [ ] Blade view namespace and published view paths
- [ ] Vite aliases `@parity/runtime` and `@parity/components`, and the plugin path from Phase 4 if it lands first
- [ ] Publish tags
- [ ] `README.md`, all of `docs/`, `CHANGELOG.md`, `SECURITY.md`, issue templates
- [ ] Rename the GitHub repository and confirm the redirect works for existing clones
- [ ] Migrate the reference theme in the same change: composer requirement, path repo, integration namespace, Vite config, generated artifacts, npm scripts
- [ ] Full test suite, `parity:doctor`, and a clean theme build green before merging

**Done when:** the string `sprout` appears nowhere in the package except historical `CHANGELOG.md` entries.

---

## Phase 4 — Zero-config install

Implements [D7](#d7-how-does-a-project-wire-up-the-js-side--resolved-first-party-vite-plugin-shipped-inside-the-composer-package). The reference theme currently needs about ten distinct pieces of setup to use the package. Most of them are things the package could do itself.

**Target install:** `composer require`, one line in `vite.config.js`, write components. Everything else derived or optional.

### Ship the Vite plugin

- [ ] Add `vite.js` to the package root exporting a `parity()` plugin
- [ ] The plugin registers the `@parity/runtime` and `@parity/components` aliases, removing the hardcoded `vendor/` paths from application config
- [ ] Serve `virtual:parity/components` from the committed `manifest.json`, replacing the generated `components.js` on disk
- [ ] Emit types for the virtual module so prop checking survives the move
- [ ] Warn at build time when the manifest is missing or older than the discovered components, instead of failing silently
- [ ] Keep `predev` / `prebuild` manifest sync in the consuming project, since regenerating the manifest genuinely needs a booted host
- [ ] Verify the plugin resolves correctly for both a Sage theme (vendor inside the theme) and a plain Laravel app

### Move editor bootstrapping into the package

- [ ] `editorPreview.js` in the reference theme bridges `window.parent.parity.config` into the Gutenberg iframe — every WordPress consumer needs exactly this, so it belongs in the package runtime
- [ ] Provide an opt-in helper for booting Alpine inside the canvas rather than each theme wiring it by hand
- [ ] Confirm the `parity` script handle dependency is the only editor enqueue step a theme must perform

### Make configuration optional

- [ ] Ship sensible defaults so a project with no published config renders correctly
- [ ] `config/parity/presets.php` should be additive, not required
- [ ] Reduce the published-file footprint to the smallest set a real project actually edits
- [ ] Keep host-specific transforms in the consuming project — that layer is genuinely project-specific and stays

### Prove it

- [ ] Write `docs/installation.md` as a numbered list and hold it to four steps or fewer
- [ ] Install into a scratch Sage theme from scratch, following only the docs, and record every point where the docs were insufficient
- [ ] Install into a bare Laravel app and confirm the Blade side works with no WordPress present

**Done when:** the reference theme's Parity-specific setup is a Composer requirement, one Vite plugin line, an integration provider for its own transforms, and its component classes.

---

## Phase 5 — Release engineering

Ends with a `1.0.0` tag on Packagist.

### Distribution

- [ ] Add `pixelfear/composer-dist-plugin` so built artifacts ship without committing `dist/`
- [ ] Stop committing `dist/parity.js`; add to `.gitignore` and `export-ignore`
- [ ] **Verify** the editor asset resolver still finds `vendor/adamalexandersson/parity/dist/parity.js` after the dist swap — the WordPress adapter resolves that path directly
- [ ] Tag-triggered workflow: build, test, create release, attach dist artifact
- [ ] Packagist webhook so tags sync automatically
- [ ] Switch the editor script version from `filemtime` to a content hash, so cache busting survives deploys that do not preserve mtimes

### Documentation

- [ ] `docs/installation.md` — Laravel and Sage/Acorn paths (from Phase 4)
- [ ] `docs/components.md` — authoring guide built around `compose()`
- [ ] `docs/schema-v1.md` — full reference, rewritten in Phase 2 vocabulary
- [ ] `docs/editor.md` — Gutenberg integration, globals, the manifest, the Vite plugin
- [ ] `docs/transforms.md`
- [ ] `docs/classes.md` — strategies, Tailwind, BEM
- [ ] `docs/hosts.md` — the Laravel and WordPress adapters
- [ ] `docs/testing.md` — how parity works, how to add fixtures
- [ ] `docs/upgrading.md`
- [ ] README trimmed to overview plus links

### Pre-release checklist

- [ ] Phases 1 through 4 complete
- [ ] No `sprout` identifiers remain anywhere in the public surface
- [ ] `parity:doctor` green on the reference theme
- [ ] Parity coverage test green
- [ ] Matrix CI green
- [ ] Package installs and renders in a bare Laravel app with no WordPress present
- [ ] A component written only from the docs renders identically in Blade and the editor
- [ ] `CHANGELOG.md` written for 1.0

---

## Phase 6 — BEM and non-Tailwind support

Ships as 1.1, on the class-strategy seam and the `classRules` mode shape from [D4](#d4-where-do-bem-element-and-modifier-calls-live-in-the-schema--resolved-classrules-with-a-mode-flag). Additive, no breaking changes, no schema major.

- [ ] `->element('header')` producing `block__element`, serialized as `mode: 'element'`
- [ ] `->modifier('type', 'list')` producing `block--modifier`, serialized as `mode: 'modifier'`, driven by prop values
- [ ] Block name derived from the component name, overridable
- [ ] `config('parity.bem')` for separators, defaulting to `__` and `--`
- [ ] Configurable modifier value formatting (kebab-case, raw)
- [ ] BEM as a selectable class strategy, composable with literal classes
- [ ] Mirror the generator in JS so parity holds
- [ ] Parity fixtures for every BEM feature
- [ ] `docs/classes.md` BEM section with a full worked example

---

## Phase 7 — Migration guide

For themes on the pre-Parity DSL config. Written **while** migrating the reference theme, frozen once the schema is frozen at 1.0 — drafting it against a moving schema means rewriting it.

- [ ] `docs/migrating-from-dsl.md`, written to be usable as AI agent context
- [ ] Old-pattern to new-pattern mapping table
- [ ] Worked examples: a simple component, a component with matches, a component with slots, an interactive component
- [ ] Common pitfalls, especially boolean and enum normalization
- [ ] `parity:doctor --legacy` mode that scans a project for old DSL config and reports specifically what to change

The guide tells an agent *how*; the command tells it *where*. Prose alone leaves the agent pattern-matching.

---

## Reference theme track (Sleak)

Sleak is the proving ground. Anything Sleak needs a workaround for is a package gap.

- [x] Retire legacy React components
- [x] Shrink `window.sleak` to icons plus AJAX config
- [x] Boot Alpine in the Gutenberg canvas (iframe preview; block canvas uses `suppress` + React-owned interactive state)
- [x] Dual-driver editor interactivity for organizers (tabs/accordion: attributes/context, no Alpine sync hacks)
- [ ] Migrate remaining components to schema-only
- [ ] Migrate all 22 components to `compose()` and the new vocabulary alongside Phase 2
- [ ] Adopt the Vite plugin and delete the manual aliases and generated module in Phase 4
- [ ] Delete `editorPreview.js` bridging once the package owns it
- [ ] Keep `wp acorn parity:doctor` green in the theme's own CI
- [ ] Track every workaround as a package issue rather than fixing it locally
- [ ] Consider extracting a starter theme once 1.0 is out

---

## Versioning policy

Two versions travel together and the relationship needs stating explicitly, because users will ask.

**Package version** follows semver on the PHP and JS API: builder methods, config keys, service contracts, host adapters, published files.

**Schema version** (`Parity\Schema\Version::CURRENT`, currently `1.0`) versions the serialized schema contract consumed by the editor runtime.

- A schema **minor** bump means additive keys or new `mode` values. Older runtimes ignore them; newer runtimes read older schemas.
- A schema **major** bump means a breaking serialization change and requires a package major.
- A package major does **not** necessarily imply a schema major.

Everything in Phases 1 through 4 is pre-1.0 and therefore exempt; the policy starts at the 1.0 tag. Per D4, BEM support is additive and does not bump the schema major.

- [ ] Document this in `docs/upgrading.md`
- [ ] Runtime warns on schema major mismatch, tolerates minor differences
- [ ] Every schema change noted in `CHANGELOG.md` with its schema-version impact

---

## Known issues

Phase 1 cleanup items (unreachable `ComponentRegistry`, no-op schema cache, hardcoded theme chevron) are addressed. Open follow-ups live in later roadmap phases.