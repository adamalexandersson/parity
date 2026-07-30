<?php

use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;

it('evaluates the extended condition operators', function () {
    $schema = require __DIR__.'/Fixtures/Schemas/conditions-operators.php';
    $renderer = new SchemaRenderer(new TransformRegistry);

    $attributes = $renderer->renderComponentAttributes($schema, [
        'size' => 'md',
        'count' => 2,
        'label' => 'pro plan',
        'note' => '',
    ], 'conditions-operators');

    $class = $attributes['class'] ?? '';

    expect($class)->toContain('in-size')
        ->and($class)->toContain('not-in-size')
        ->and($class)->toContain('gt-count')
        ->and($class)->toContain('gte-count')
        ->and($class)->toContain('lt-count')
        ->and($class)->toContain('lte-count')
        ->and($class)->toContain('contains-label')
        ->and($class)->toContain('empty-note')
        ->and($class)->toContain('not-empty-label')
        ->and($class)->toContain('nested-group');
});
