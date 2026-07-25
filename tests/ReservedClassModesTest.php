<?php

use Sprout\Registries\TransformRegistry;
use Sprout\Render\SchemaRenderer;

it('ignores reserved bem class-rule modes', function () {
    $schema = [
        'schemaVersion' => '1.0',
        'name' => 'bem-reserved',
        'tag' => 'div',
        'classRules' => [
            ['classes' => 'keep', 'condition' => null],
            ['mode' => 'element', 'element' => 'header', 'classes' => 'should-not-apply', 'condition' => null],
            ['mode' => 'modifier', 'modifier' => 'type', 'classes' => 'also-skip', 'condition' => null],
        ],
    ];

    $attributes = (new SchemaRenderer(new TransformRegistry))
        ->renderComponentAttributes($schema, []);

    expect($attributes['class'] ?? '')->toBe('keep')
        ->and($attributes['class'] ?? '')->not->toContain('should-not-apply')
        ->and($attributes['class'] ?? '')->not->toContain('also-skip');
});
