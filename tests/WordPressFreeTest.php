<?php

use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;
use Parity\Support\ClassFactory;

it('renders schemas without wordpress escape helpers defined', function () {
    expect(function_exists('esc_attr'))->toBeFalse()
        ->and(function_exists('esc_url'))->toBeFalse()
        ->and(function_exists('apply_filters'))->toBeFalse();

    $factory = new ClassFactory;
    $factory->apply('text-sm');

    expect($factory->get())->toBe('text-sm');

    $renderer = new SchemaRenderer(new TransformRegistry);
    $attributes = $renderer->renderComponentAttributes([
        'schemaVersion' => '1.0',
        'name' => 'demo',
        'classRules' => [
            ['classes' => 'inline-flex', 'condition' => null],
        ],
        'attributes' => [
            [
                'name' => 'href',
                'source' => 'href',
                'cast' => 'url',
                'condition' => null,
            ],
        ],
    ], ['href' => 'https://example.com']);

    expect($attributes['class'] ?? '')->toContain('inline-flex')
        ->and($attributes['href'] ?? '')->toBe('https://example.com');
});
