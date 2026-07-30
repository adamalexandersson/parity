<?php

use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;

it('applies attr and style match outcomes in php', function () {
    $schema = require __DIR__.'/Fixtures/Schemas/outcomes-attr-style.php';
    $renderer = new SchemaRenderer(new TransformRegistry);

    $attributes = $renderer->renderComponentAttributes($schema, ['state' => 'disabled'], 'outcomes-attr-style');

    expect($attributes['disabled'] ?? null)->toBeTrue()
        ->and($attributes['class'] ?? '')->toContain('is-disabled')
        ->and($attributes['style'] ?? '')->toContain('opacity: 0.5');
});

it('applies aria attr outcomes without disabling', function () {
    $schema = require __DIR__.'/Fixtures/Schemas/outcomes-attr-style.php';
    $renderer = new SchemaRenderer(new TransformRegistry);

    $attributes = $renderer->renderComponentAttributes($schema, ['state' => 'active'], 'outcomes-attr-style');

    expect($attributes['aria-pressed'] ?? null)->toBe('true')
        ->and($attributes)->not->toHaveKey('disabled')
        ->and($attributes['class'] ?? '')->toContain('is-active');
});
