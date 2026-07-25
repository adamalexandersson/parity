<?php

use Sprout\Registries\TransformRegistry;
use Sprout\Render\SchemaRenderer;

it('renders button classes from schema', function () {
    $schema = require __DIR__.'/fixtures/button.php';
    $renderer = new SchemaRenderer(new TransformRegistry);

    $attributes = $renderer->renderComponentAttributes($schema, [
        'size' => 'sm',
        'themeColor' => 'primary',
        'themeType' => 'solid',
        'pill' => 'true',
        'arrow' => true,
    ], 'button');

    expect($attributes['class'])->toContain('inline-flex')
        ->and($attributes['class'])->toContain('px-4 py-2 text-sm')
        ->and($attributes['class'])->toContain('bg-primary-500 text-white')
        ->and($attributes['class'])->toContain('rounded-full')
        ->and($attributes['data-component'])->toBe('button');
});
