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
];
