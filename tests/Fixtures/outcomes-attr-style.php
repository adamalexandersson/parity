<?php

return [
    'schemaVersion' => '1.0',
    'name' => 'outcomes-attr-style',
    'tag' => 'button',
    'classRules' => [
        ['classes' => 'btn', 'condition' => null],
    ],
    'matches' => [
        [
            'props' => ['state'],
            'cases' => [
                [
                    'values' => ['disabled'],
                    'outcomes' => [
                        ['type' => 'classes', 'value' => 'is-disabled'],
                        ['type' => 'attr', 'name' => 'disabled', 'value' => true],
                        ['type' => 'style', 'property' => 'opacity', 'value' => '0.5'],
                    ],
                ],
                [
                    'values' => ['active'],
                    'outcomes' => [
                        ['type' => 'classes', 'value' => 'is-active'],
                        ['type' => 'attr', 'name' => 'aria-pressed', 'value' => 'true'],
                    ],
                ],
            ],
            'default' => [
                ['type' => 'classes', 'value' => 'is-idle'],
            ],
        ],
    ],
    'attributes' => [],
    'styles' => [],
    'children' => [],
];
