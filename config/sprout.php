<?php

return [

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
    | static schema() method.
    |
    */
    'components' => [
        'namespace' => 'App\\View\\Components',
        'path' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Common attribute maps
    |--------------------------------------------------------------------------
    |
    | Shared value-to-class maps referenced via ->includeCommon('cols').
    |
    */
    'common' => [],

    /*
    |--------------------------------------------------------------------------
    | Design tokens
    |--------------------------------------------------------------------------
    |
    | Token tables for ->apply('p', 'md') style class resolution.
    |
    */
    'tokens' => [],

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
        'config_global' => 'componentConfig',
        'runtime_global' => 'sprout',
        'exports_path' => 'resources/js/sprout/components.js',
    ],

];
