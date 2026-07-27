<?php

return [
    'section-default-slot' => [
        'schema' => require __DIR__.'/section.php',
        'props' => [],
        'defaultSlot' => 'content',
        'slotTargets' => ['content'],
    ],
    'alert-nested-default-slot' => [
        'schema' => require __DIR__.'/alert.php',
        'props' => ['type' => 'info'],
        'defaultSlot' => 'wrapper.content',
        'slotTargets' => ['wrapper.content'],
    ],
    'card-named-slots' => [
        'schema' => require __DIR__.'/card-slots.php',
        'props' => [],
        'defaultSlot' => 'body.inner.content',
        'slotTargets' => ['body.inner.content'],
    ],
    'void-media' => [
        'schema' => require __DIR__.'/void-media.php',
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
        'schema' => require __DIR__.'/component-ref.php',
        'props' => ['type' => 'info'],
        'defaultSlot' => null,
        'slotTargets' => [],
    ],
    'component-ref-missing-mapping' => [
        'schema' => require __DIR__.'/component-ref.php',
        'props' => ['type' => 'unknown'],
        'defaultSlot' => null,
        'slotTargets' => [],
    ],
];
