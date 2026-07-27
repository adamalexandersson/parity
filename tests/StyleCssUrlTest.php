<?php

use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;

it('wraps a resolved background image url in css url()', function () {
    $transforms = new TransformRegistry;
    $transforms->register('imageUrl', fn ($value) => is_string($value) ? $value : null);

    $renderer = new SchemaRenderer($transforms);

    $schema = [
        'schemaVersion' => '1.0',
        'styles' => [
            [
                'property' => 'background-image',
                'source' => 'backgroundImage',
                'cast' => 'imageUrl',
                'cssUrl' => true,
                'condition' => ['prop' => 'backgroundImage', 'operator' => 'truthy'],
            ],
        ],
    ];

    $attributes = $renderer->renderComponentAttributes(
        $schema,
        ['backgroundImage' => 'https://picsum.photos/1920/800'],
        'section'
    );

    expect($attributes['style'] ?? '')->toBe(
        'background-image: url(https://picsum.photos/1920/800)'
    );
});

it('does not double wrap existing url() syntax', function () {
    $renderer = new SchemaRenderer(new TransformRegistry);

    $schema = [
        'schemaVersion' => '1.0',
        'styles' => [
            [
                'property' => 'background-image',
                'source' => 'backgroundImage',
                'cast' => 'string',
                'cssUrl' => true,
                'condition' => ['prop' => 'backgroundImage', 'operator' => 'truthy'],
            ],
        ],
    ];

    $attributes = $renderer->renderComponentAttributes(
        $schema,
        ['backgroundImage' => 'url(https://example.com/image.jpg)'],
        'section'
    );

    expect($attributes['style'] ?? '')->toBe(
        'background-image: url(https://example.com/image.jpg)'
    );
});
