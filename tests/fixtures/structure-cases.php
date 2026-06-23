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
];
