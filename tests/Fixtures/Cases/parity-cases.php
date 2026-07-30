<?php

return [
    'button-sm-primary' => [
        'schema' => require __DIR__.'/../Schemas/button.php',
        'props' => [
            'size' => 'sm',
            'themeColor' => 'primary',
            'themeType' => 'solid',
            'pill' => 'true',
            'arrow' => true,
        ],
    ],
    'button-lg-default' => [
        'schema' => require __DIR__.'/../Schemas/button.php',
        'props' => [
            'size' => 'lg',
            'themeColor' => 'default',
            'themeType' => 'solid',
            'pill' => 'false',
            'arrow' => false,
        ],
    ],
    'button-md-outline' => [
        'schema' => require __DIR__.'/../Schemas/button.php',
        'props' => [
            'size' => 'md',
            'themeColor' => 'primary',
            'themeType' => 'outline',
            'pill' => 'false',
            'arrow' => false,
        ],
    ],
    'docs-button-lg-primary' => [
        'schema' => require __DIR__.'/../Schemas/docs-button.php',
        'props' => [
            'size' => 'lg',
            'themeColor' => 'primary',
            'themeType' => 'solid',
        ],
    ],
    'badge-md-primary' => [
        'schema' => require __DIR__.'/../Schemas/badge.php',
        'props' => [
            'size' => 'md',
            'themeColor' => 'primary',
            'themeType' => 'solid',
            'pill' => 'true',
            'equilateral' => 'false',
        ],
    ],
    'link-md-arrow' => [
        'schema' => require __DIR__.'/../Schemas/link.php',
        'props' => [
            'size' => 'md',
            'themeColor' => 'primary',
            'hasArrow' => true,
        ],
    ],
    'conditions-any-affordance' => [
        'schema' => require __DIR__.'/../Schemas/conditions-any-all.php',
        'props' => [
            'arrow' => true,
            'icon' => false,
            'href' => 'https://example.com',
            'external' => true,
            'size' => 'sm',
            'hidden' => false,
            'count' => 2,
            'opacity' => '0.9',
        ],
        'config' => [
            'tokens' => [
                'gap' => [
                    'md' => 'gap-4',
                ],
            ],
        ],
    ],
    'conditions-all-safe-link' => [
        'schema' => require __DIR__.'/../Schemas/conditions-any-all.php',
        'props' => [
            'arrow' => false,
            'icon' => false,
            'href' => 'https://example.com',
            'external' => false,
            'size' => 'md',
            'count' => 0,
        ],
        'config' => [
            'tokens' => [
                'gap' => [
                    'md' => 'gap-4',
                ],
            ],
        ],
    ],
    'bem-badge' => [
        'schema' => require __DIR__.'/../Schemas/bem-badge.php',
        'props' => [
            'pill' => true,
            'size' => 'md',
            'themeColor' => 'primary',
            'themeType' => 'outline',
            'active' => true,
            'icon' => true,
        ],
        'config' => [
            'classes' => ['strategy' => 'passthrough'],
        ],
    ],
    'bem-button' => [
        'schema' => require __DIR__.'/../Schemas/bem-button.php',
        'props' => [
            'pill' => true,
            'size' => 'md',
            'themeColor' => 'primary',
            'themeType' => 'solid',
            'active' => true,
            'icon' => true,
            'arrow' => true,
        ],
        'config' => [
            'classes' => ['strategy' => 'passthrough'],
        ],
    ],
    'bem-button-inactive' => [
        'schema' => require __DIR__.'/../Schemas/bem-button.php',
        'props' => [
            'pill' => false,
            'size' => 'lg',
            'themeColor' => 'primary',
            'themeType' => 'outline',
            'active' => false,
            'icon' => false,
            'arrow' => false,
        ],
        'config' => [
            'classes' => ['strategy' => 'passthrough'],
        ],
    ],
    'kebab-badge' => [
        'schema' => require __DIR__.'/../Schemas/kebab-badge.php',
        'props' => [
            'pill' => true,
            'size' => 'md',
            'themeColor' => 'primary',
            'active' => true,
        ],
        'config' => [
            'classes' => ['strategy' => 'passthrough'],
        ],
    ],
    'bem-grid-responsive' => [
        'schema' => require __DIR__.'/../Schemas/bem-grid.php',
        'props' => [
            'colsMd' => 2,
        ],
        'config' => [
            'classes' => ['strategy' => 'passthrough'],
        ],
    ],
    'bem-grid-inferred' => [
        'schema' => require __DIR__.'/../Schemas/bem-grid-inferred.php',
        'props' => [
            'colsMd' => 2,
        ],
        'config' => [
            'classes' => ['strategy' => 'passthrough'],
        ],
    ],
    'kebab-grid-responsive' => [
        'schema' => require __DIR__.'/../Schemas/kebab-grid.php',
        'props' => [
            'colsMd' => 2,
        ],
        'config' => [
            'classes' => ['strategy' => 'passthrough'],
        ],
    ],
    'bem-organizer' => [
        'schema' => require __DIR__.'/../Schemas/bem-organizer.php',
        'props' => [
            'orientation' => 'vertical',
        ],
        'config' => [
            'classes' => ['strategy' => 'passthrough'],
        ],
    ],
    'naming-omission' => [
        'schema' => require __DIR__.'/../Schemas/naming-omission.php',
        'props' => [
            'featured' => false,
            'size' => '',
            'active' => false,
            'icon' => false,
            'showHidden' => false,
            'hiddenFlag' => true,
        ],
        'config' => [
            'classes' => ['strategy' => 'passthrough'],
        ],
    ],
];
