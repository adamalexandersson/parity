<?php

return [
    'section-default-slot' => [
        'schema' => require __DIR__.'/../Schemas/section.php',
        'props' => [],
        'defaultSlot' => 'content',
        'slotTargets' => ['content'],
    ],
    'alert-nested-default-slot' => [
        'schema' => require __DIR__.'/../Schemas/alert.php',
        'props' => ['type' => 'info'],
        'defaultSlot' => 'wrapper.content',
        'slotTargets' => ['wrapper.content'],
    ],
    'card-named-slots' => [
        'schema' => require __DIR__.'/../Schemas/card-slots.php',
        'props' => [],
        'defaultSlot' => 'body.inner.content',
        'slotTargets' => ['body.inner.content'],
    ],
    'void-media' => [
        'schema' => require __DIR__.'/../Schemas/void-media.php',
        'props' => [
            'src' => 'https://example.com/image.jpg',
            'alt' => 'Example',
            'name' => 'token',
            'disabled' => true,
        ],
        'defaultSlot' => null,
        'slotTargets' => [],
    ],
    'component-ref-resolving' => [
        'schema' => require __DIR__.'/../Schemas/component-ref.php',
        'props' => ['type' => 'info'],
        'defaultSlot' => null,
        'slotTargets' => [],
    ],
    'component-ref-missing-mapping' => [
        'schema' => require __DIR__.'/../Schemas/component-ref.php',
        'props' => ['type' => 'unknown'],
        'defaultSlot' => null,
        'slotTargets' => [],
    ],
    'bem-badge-element' => [
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
        'defaultSlot' => 'content',
        'slotTargets' => ['content'],
    ],
    'kebab-badge-element' => [
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
        'defaultSlot' => 'content',
        'slotTargets' => ['content'],
    ],
    'bem-button-elements' => [
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
        'defaultSlot' => 'label',
        'slotTargets' => ['label'],
    ],
    'bem-button-elements-no-affordance' => [
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
        'defaultSlot' => 'label',
        'slotTargets' => ['label'],
    ],
];
