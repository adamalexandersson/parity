<?php

return [
    'schemaVersion' => '1.0',
    'name' => 'conditions-any-all',
    'tag' => 'div',
    'classRules' => [
        ['classes' => 'base', 'condition' => null],
        [
            'classes' => 'has-affordance',
            'condition' => [
                'operator' => 'any',
                'conditions' => [
                    ['prop' => 'arrow', 'operator' => 'truthy'],
                    ['prop' => 'icon', 'operator' => 'truthy'],
                ],
            ],
        ],
        [
            'classes' => 'safe-link',
            'condition' => [
                'operator' => 'all',
                'conditions' => [
                    ['prop' => 'href', 'operator' => 'truthy'],
                    ['prop' => 'external', 'operator' => 'falsy'],
                ],
            ],
        ],
        [
            'classes' => '',
            'mode' => 'token',
            'tokenGroup' => 'gap',
            'token' => 'md',
            'condition' => null,
        ],
        [
            'classes' => '',
            'mode' => 'element',
            'element' => 'header',
            'condition' => null,
        ],
        [
            'classes' => '',
            'mode' => 'modifier',
            'modifier' => 'type',
            'condition' => null,
        ],
    ],
    'matches' => [
        [
            'props' => ['size'],
            'cases' => [
                [
                    'values' => ['sm'],
                    'outcomes' => [
                        ['type' => 'classes', 'value' => 'text-sm'],
                        ['type' => 'attr', 'name' => 'data-size', 'value' => 'sm'],
                        ['type' => 'style', 'property' => 'font-size', 'value' => '14px'],
                    ],
                ],
            ],
            'default' => [
                ['type' => 'classes', 'value' => 'text-base'],
            ],
        ],
    ],
    'attributes' => [
        [
            'name' => 'data-role',
            'source' => null,
            'value' => 'demo',
            'cast' => 'string',
            'condition' => null,
        ],
        [
            'name' => 'hidden',
            'source' => 'hidden',
            'cast' => 'boolean',
            'condition' => ['prop' => 'hidden', 'operator' => 'truthy'],
        ],
        [
            'name' => 'data-count',
            'source' => 'count',
            'cast' => 'integer',
            'condition' => ['prop' => 'count', 'operator' => 'notEquals', 'value' => 0],
        ],
        [
            'name' => 'href',
            'source' => 'href',
            'cast' => 'url',
            'condition' => ['prop' => 'href', 'operator' => 'equals', 'value' => 'https://example.com'],
        ],
    ],
    'styles' => [
        [
            'property' => 'opacity',
            'source' => 'opacity',
            'cast' => 'string',
            'condition' => ['prop' => 'opacity', 'operator' => 'truthy'],
        ],
        [
            'property' => 'background-image',
            'source' => 'backgroundImage',
            'cast' => 'cssUrl',
            'cssUrl' => true,
            'condition' => ['prop' => 'backgroundImage', 'operator' => 'truthy'],
        ],
    ],
    'children' => [
        'content' => [
            'tag' => null,
            'fragment' => true,
            'path' => 'content',
            'slot' => ['name' => null, 'default' => true],
            'children' => [],
        ],
        'footer' => [
            'tag' => 'div',
            'path' => 'footer',
            'slot' => ['name' => 'footer', 'default' => false],
            'children' => [],
        ],
    ],
    'defaultSlot' => 'content',
    'namedSlots' => ['footer'],
];
