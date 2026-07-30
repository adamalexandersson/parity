<?php

return [
    'schemaVersion' => '1.0',
    'name' => 'conditions-operators',
    'tag' => 'div',
    'classRules' => [
        ['classes' => 'base', 'condition' => null],
        [
            'classes' => 'in-size',
            'condition' => ['prop' => 'size', 'operator' => 'in', 'value' => ['sm', 'md']],
        ],
        [
            'classes' => 'not-in-size',
            'condition' => ['prop' => 'size', 'operator' => 'notIn', 'value' => ['xl']],
        ],
        [
            'classes' => 'gt-count',
            'condition' => ['prop' => 'count', 'operator' => 'gt', 'value' => 1],
        ],
        [
            'classes' => 'gte-count',
            'condition' => ['prop' => 'count', 'operator' => 'gte', 'value' => 2],
        ],
        [
            'classes' => 'lt-count',
            'condition' => ['prop' => 'count', 'operator' => 'lt', 'value' => 10],
        ],
        [
            'classes' => 'lte-count',
            'condition' => ['prop' => 'count', 'operator' => 'lte', 'value' => 2],
        ],
        [
            'classes' => 'contains-label',
            'condition' => ['prop' => 'label', 'operator' => 'contains', 'value' => 'pro'],
        ],
        [
            'classes' => 'empty-note',
            'condition' => ['prop' => 'note', 'operator' => 'empty'],
        ],
        [
            'classes' => 'not-empty-label',
            'condition' => ['prop' => 'label', 'operator' => 'notEmpty'],
        ],
        [
            'classes' => 'nested-group',
            'condition' => [
                'operator' => 'any',
                'conditions' => [
                    [
                        'operator' => 'all',
                        'conditions' => [
                            ['prop' => 'count', 'operator' => 'gte', 'value' => 2],
                            ['prop' => 'label', 'operator' => 'contains', 'value' => 'pro'],
                        ],
                    ],
                    ['prop' => 'force', 'operator' => 'truthy'],
                ],
            ],
        ],
    ],
    'matches' => [],
    'attributes' => [],
    'styles' => [],
    'children' => [],
];
