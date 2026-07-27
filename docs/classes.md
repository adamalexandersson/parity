# Classes

Parity composes class strings from literal rules, `match` outcomes, presets, and tokens. The strategy controls conflict resolution.

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

## BEM (1.1)

BEM element and modifier generation (`->element()`, `->modifier()`) ships as package **1.1** on the class-strategy seam. The schema already reserves `mode: "element"` and `mode: "modifier"`; current runtimes ignore them. Additive — no schema major bump. See the roadmap Phase 6 for the planned API.
