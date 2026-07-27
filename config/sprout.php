<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Host environment
    |--------------------------------------------------------------------------
    |
    | null = auto-detect (WordPress when add_action exists, otherwise Laravel).
    | Override with "laravel" or "wordpress".
    |
    */
    'host' => env('SPROUT_HOST'),

    /*
    |--------------------------------------------------------------------------
    | Schema version
    |--------------------------------------------------------------------------
    */
    'schema_version' => '1.0',

    /*
    |--------------------------------------------------------------------------
    | Component discovery
    |--------------------------------------------------------------------------
    |
    | Namespace prefix and path for Sprout Blade components that expose a
    | static compose() method.
    |
    */
    'components' => [
        'namespace' => 'App\\View\\Components',
        'path' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Preset class maps
    |--------------------------------------------------------------------------
    |
    | Shared value-to-class maps referenced via ->preset('cols').
    |
    */
    'presets' => [],

    /*
    |--------------------------------------------------------------------------
    | Design tokens
    |--------------------------------------------------------------------------
    |
    | Token tables for ->token('p', 'md') style class resolution.
    |
    */
    'tokens' => [],

    /*
    |--------------------------------------------------------------------------
    | Class composition strategy
    |--------------------------------------------------------------------------
    |
    | "tailwind" uses tailwind-merge (default). "passthrough" concatenates and
    | deduplicates class tokens without conflict resolution.
    |
    */
    'classes' => [
        'strategy' => env('SPROUT_CLASS_STRATEGY', 'tailwind'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default component shell view
    |--------------------------------------------------------------------------
    |
    | Used when a Sprout component has no theme blade override at
    | resources/views/components/{namespace}/{name}.blade.php.
    |
    */
    'shell_view' => 'Sprout::shell',

    /*
    |--------------------------------------------------------------------------
    | Editor script
    |--------------------------------------------------------------------------
    */
    'editor' => [
        'script_handle' => 'sprout',
        'script_path' => 'dist/sprout.js',
        'runtime_global' => 'sprout',
        'exports_path' => 'resources/js/sprout/components.js',
        'manifest_path' => 'resources/js/sprout/manifest.json',
        'types_path' => 'resources/js/sprout/components.d.ts',
        /*
        | "suppress" (default) — strip Alpine x-* / :bind attrs in Gutenberg.
        | "emit" — leave Alpine attrs on the canvas (requires Alpine in the editor).
        */
        'alpine' => env('SPROUT_EDITOR_ALPINE', 'suppress'),

        /*
        | When true, PHP SchemaRenderer throws on unknown match outcomes even if
        | app.debug is off. The editor still uses Host::isDebug() / window.sprout.config.debug.
        */
        'debug' => env('SPROUT_EDITOR_DEBUG', false),
    ],

];
