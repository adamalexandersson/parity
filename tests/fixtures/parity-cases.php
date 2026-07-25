<?php

return [
    'button-sm-primary' => [
        'schema' => require __DIR__.'/button.php',
        'props' => [
            'size' => 'sm',
            'themeColor' => 'primary',
            'themeType' => 'solid',
            'pill' => 'true',
            'arrow' => true,
        ],
    ],
    'button-lg-default' => [
        'schema' => require __DIR__.'/button.php',
        'props' => [
            'size' => 'lg',
            'themeColor' => 'default',
            'themeType' => 'solid',
            'pill' => 'false',
            'arrow' => false,
        ],
    ],
    'button-md-outline' => [
        'schema' => require __DIR__.'/button.php',
        'props' => [
            'size' => 'md',
            'themeColor' => 'primary',
            'themeType' => 'outline',
            'pill' => 'false',
            'arrow' => false,
        ],
    ],
    'badge-md-primary' => [
        'schema' => require __DIR__.'/badge.php',
        'props' => [
            'size' => 'md',
            'themeColor' => 'primary',
            'themeType' => 'solid',
            'pill' => 'true',
            'equilateral' => 'false',
        ],
    ],
    'link-md-arrow' => [
        'schema' => require __DIR__.'/link.php',
        'props' => [
            'size' => 'md',
            'themeColor' => 'primary',
            'hasArrow' => true,
        ],
    ],
    'conditions-any-affordance' => [
        'schema' => require __DIR__.'/conditions-any-all.php',
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
        'schema' => require __DIR__.'/conditions-any-all.php',
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
];
