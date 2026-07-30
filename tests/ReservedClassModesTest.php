<?php

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;
use Parity\Support\ClassNameGenerator;

beforeEach(function () {
    $container = Container::getInstance();

    if (! $container->bound('config')) {
        $container->instance('config', new Repository([]));
    }

    config([
        'parity.classes.strategy' => 'passthrough',
        'parity.bem' => ClassNameGenerator::defaultBem(),
        'parity.variant' => ClassNameGenerator::defaultVariant(),
        'parity.state' => ClassNameGenerator::defaultState(),
    ]);
});

it('generates bem and kebab class names from naming rules', function () {
    $schema = [
        'schemaVersion' => '1.0',
        'name' => 'status-badge',
        'block' => 'badge',
        'category' => 'component',
        'tag' => 'div',
        'classRules' => [
            ['classes' => '', 'condition' => null, 'mode' => 'modifier', 'source' => 'pill', 'as' => 'pill', 'breakpoint' => null],
            ['classes' => '', 'condition' => null, 'mode' => 'modifier', 'source' => 'size', 'as' => 'size', 'breakpoint' => null],
            ['classes' => '', 'condition' => null, 'mode' => 'state', 'state' => 'is', 'stateName' => 'active', 'source' => 'active'],
        ],
    ];

    $attributes = (new SchemaRenderer(new TransformRegistry))
        ->renderComponentAttributes($schema, [
            'pill' => true,
            'size' => 'md',
            'active' => true,
        ]);

    expect(normalizeClassString($attributes['class'] ?? ''))
        ->toBe('c-badge c-badge--pill c-badge--size-md is-active');
});

it('skips the classes field on naming modes', function () {
    $schema = [
        'schemaVersion' => '1.0',
        'name' => 'badge',
        'category' => 'component',
        'tag' => 'div',
        'classRules' => [
            ['classes' => 'keep', 'condition' => null],
            ['mode' => 'modifier', 'source' => 'pill', 'as' => 'pill', 'classes' => 'also-skip', 'condition' => null],
            ['mode' => 'state', 'state' => 'is', 'stateName' => 'active', 'source' => 'active', 'classes' => 'should-not-apply', 'condition' => null],
        ],
    ];

    $attributes = (new SchemaRenderer(new TransformRegistry))
        ->renderComponentAttributes($schema, [
            'pill' => true,
            'active' => true,
        ]);

    expect(normalizeClassString($attributes['class'] ?? ''))
        ->toBe('c-badge c-badge--pill is-active keep')
        ->and($attributes['class'] ?? '')->not->toContain('should-not-apply')
        ->and($attributes['class'] ?? '')->not->toContain('also-skip');
});
