<?php

return [
    'schemaVersion' => '1.0',
    'name' => 'unique-id-a11y',
    'tag' => 'div',
    'classRules' => [],
    'matches' => [],
    'attributes' => [],
    'styles' => [],
    'children' => [
        'label' => [
            'tag' => 'label',
            'fragment' => false,
            'attributes' => [
                [
                    'name' => 'for',
                    'idRef' => 'field',
                    'source' => null,
                    'value' => null,
                    'cast' => 'string',
                    'condition' => null,
                ],
            ],
            'classRules' => [],
            'matches' => [],
            'styles' => [],
            'children' => [],
            'slot' => ['name' => 'label', 'default' => false],
        ],
        'field' => [
            'tag' => 'input',
            'fragment' => false,
            'attributes' => [
                [
                    'name' => 'id',
                    'uniqueId' => 'field',
                    'source' => null,
                    'value' => null,
                    'cast' => 'string',
                    'condition' => null,
                ],
                [
                    'name' => 'aria-describedby',
                    'idRef' => 'hint',
                    'source' => null,
                    'value' => null,
                    'cast' => 'string',
                    'condition' => null,
                ],
                [
                    'name' => 'type',
                    'source' => null,
                    'value' => 'text',
                    'cast' => 'string',
                    'condition' => null,
                ],
            ],
            'classRules' => [],
            'matches' => [],
            'styles' => [],
            'children' => [],
        ],
        'hint' => [
            'tag' => 'p',
            'fragment' => false,
            'attributes' => [
                [
                    'name' => 'id',
                    'uniqueId' => 'hint',
                    'source' => null,
                    'value' => null,
                    'cast' => 'string',
                    'condition' => null,
                ],
            ],
            'classRules' => [],
            'matches' => [],
            'styles' => [],
            'children' => [],
            'slot' => ['name' => 'hint', 'default' => false],
        ],
    ],
];
