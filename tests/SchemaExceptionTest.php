<?php

use Sprout\Exceptions\SchemaException;
use Sprout\Registries\TransformRegistry;
use Sprout\Render\SchemaRenderer;

it('formats schema exceptions with component and path', function () {
    $exception = new SchemaException('bad node', 'card', 'header.title');

    expect($exception->getMessage())->toBe('[card] header.title: bad node')
        ->and($exception->component)->toBe('card')
        ->and($exception->path)->toBe('header.title');
});

it('throws on unknown outcome types when debug is enabled', function () {
    config(['app.debug' => true]);

    $renderer = new SchemaRenderer(new TransformRegistry);
    $schema = [
        'schemaVersion' => '1.0',
        'name' => 'broken',
        'tag' => 'div',
        'classRules' => [],
        'matches' => [
            [
                'props' => ['size'],
                'cases' => [
                    [
                        'values' => ['sm'],
                        'outcomes' => [
                            ['type' => 'explode', 'value' => 'nope'],
                        ],
                    ],
                ],
                'default' => [],
            ],
        ],
        'attributes' => [],
        'styles' => [],
        'children' => [],
    ];

    expect(fn () => $renderer->renderComponentAttributes($schema, ['size' => 'sm'], 'broken'))
        ->toThrow(SchemaException::class);
});
