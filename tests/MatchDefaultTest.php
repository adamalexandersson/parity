<?php

use Parity\Component;
use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;

it('matches null theme color to an explicit default case', function () {
    $schema = Component::make('card', tag: 'div')
        ->match('boxed', 'themeColor')
        ->case(true, 'default')->classes('bg-gray-100')->end()
        ->end()
        ->toSchema();

    $renderer = new SchemaRenderer(new TransformRegistry);

    $attributes = $renderer->renderComponentAttributes($schema, [
        'boxed' => true,
        'themeColor' => null,
    ]);

    expect($attributes['class'] ?? '')->toContain('bg-gray-100');
});

it('matches a bool prop to a bool case', function () {
    $schema = Component::make('card', tag: 'div')
        ->match('boxed', 'size')
        ->case(true, 'md')->classes('p-3 md:p-4')->end()
        ->end()
        ->toSchema();

    $renderer = new SchemaRenderer(new TransformRegistry);

    $attributes = $renderer->renderComponentAttributes($schema, [
        'boxed' => true,
        'size' => 'md',
    ]);

    expect($attributes['class'] ?? '')->toContain('p-3 md:p-4');
});

it('matches string true from blade to a bool case', function () {
    $schema = Component::make('card', tag: 'div')
        ->match('stretch')
        ->case(true)->classes('h-full')->end()
        ->end()
        ->toSchema();

    $renderer = new SchemaRenderer(new TransformRegistry);

    $attributes = $renderer->renderComponentAttributes($schema, [
        'stretch' => 'true',
    ]);

    expect($attributes['class'] ?? '')->toContain('h-full');
});

it('does not coerce an integer match value to boolean', function () {
    $schema = Component::make('heading', tag: 'div')
        ->match('level')
        ->unless('size')
        ->case(1)->classes('text-6xl')->end()
        ->case(2)->classes('text-4xl')->end()
        ->end()
        ->toSchema();

    $renderer = new SchemaRenderer(new TransformRegistry);

    $attributes = $renderer->renderComponentAttributes($schema, [
        'level' => 1,
        'size' => false,
    ]);

    expect($attributes['class'] ?? '')->toContain('text-6xl')
        ->and($attributes['class'] ?? '')->not->toContain('text-4xl');
});

it('matches blade string one to a bool case', function () {
    $schema = Component::make('card', tag: 'div')
        ->match('stretch')
        ->case(true)->classes('h-full')->end()
        ->end()
        ->toSchema();

    $renderer = new SchemaRenderer(new TransformRegistry);

    $attributes = $renderer->renderComponentAttributes($schema, [
        'stretch' => '1',
    ]);

    expect($attributes['class'] ?? '')->toContain('h-full');
});
