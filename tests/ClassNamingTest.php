<?php

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Parity\Component;
use Parity\Node;
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

it('generates category prefixes and block overrides', function () {
    $generator = new ClassNameGenerator;

    expect($generator->resolveBlock(['name' => 'badge', 'category' => 'component']))->toBe('c-badge')
        ->and($generator->resolveBlock(['name' => 'status-badge', 'block' => 'badge', 'category' => 'component']))->toBe('c-badge')
        ->and($generator->resolveBlock(['name' => 'grid', 'category' => 'object']))->toBe('o-grid')
        ->and($generator->resolveBlock(['name' => 'tabs', 'category' => 'organizers']))->toBe('o-tabs')
        ->and($generator->categoryPrefix('Utilities'))->toBe('u-');
});

it('prefers inferred responsive props over the bare source', function () {
    $generator = new ClassNameGenerator;

    expect($generator->resolvePropValue(['cols' => 1, 'colsMd' => 2], 'cols', 'md'))->toBe(2)
        ->and($generator->resolvePropValue(['cols' => 1], 'cols', 'md'))->toBe(1)
        ->and($generator->resolvePropValue(['colsMd' => 3], 'colsMd', 'md'))->toBe(3);
});

it('does not auto-emit a block for pure tailwind schemas', function () {
    $schema = Component::make('button')
        ->classes('inline-flex')
        ->toSchema();

    $attributes = (new SchemaRenderer(new TransformRegistry))
        ->renderComponentAttributes($schema, []);

    expect($attributes['class'] ?? '')->toBe('inline-flex');
});

it('renders bem naming from the fluent api', function () {
    $schema = Component::make('status-badge')
        ->block('badge')
        ->category('component')
        ->modifier('pill')
        ->modifier('size')
        ->is('active')
        ->children([
            Node::make('content', tag: 'span')
                ->element('label')
                ->modifier('size')
                ->slot(),
        ])
        ->toSchema();

    $renderer = new SchemaRenderer(new TransformRegistry);
    $attributes = $renderer->renderComponentAttributes($schema, [
        'pill' => true,
        'size' => 'md',
        'active' => true,
    ]);
    $structure = $renderer->renderStructure($schema, [
        'pill' => true,
        'size' => 'md',
        'active' => true,
    ]);

    expect(normalizeClassString($attributes['class'] ?? ''))->toBe('c-badge c-badge--pill c-badge--size-md is-active')
        ->and($structure['content']['attributes']['class'] ?? '')->toBe('c-badge__label c-badge__label--size-md');
});

it('renders the bem-button fixture end to end', function () {
    $schema = require __DIR__.'/Fixtures/Schemas/bem-button.php';
    $props = [
        'pill' => true,
        'size' => 'md',
        'themeColor' => 'primary',
        'themeType' => 'solid',
        'active' => true,
        'icon' => true,
        'arrow' => true,
    ];

    $renderer = new SchemaRenderer(new TransformRegistry);
    $attributes = $renderer->renderComponentAttributes($schema, $props);
    $structure = $renderer->renderStructure($schema, $props);

    expect($schema['name'])->toBe('bem-button')
        ->and($schema['block'])->toBe('button')
        ->and($schema['category'])->toBe('component')
        ->and(normalizeClassString($attributes['class'] ?? ''))->toBe(
            'c-button c-button--pill c-button--size-md c-button--theme-primary c-button--theme-solid-primary has-arrow has-icon is-active'
        )
        ->and($structure['icon']['attributes']['class'] ?? '')->toBe('c-button__icon')
        ->and($structure['label']['attributes']['class'] ?? '')->toBe('c-button__label c-button__label--size-md')
        ->and($structure['arrow']['attributes']['class'] ?? '')->toBe('c-button__arrow');
});

it('omits bem-button states and affordances when props are falsy', function () {
    $schema = require __DIR__.'/Fixtures/Schemas/bem-button.php';
    $props = [
        'pill' => false,
        'size' => 'lg',
        'themeColor' => 'primary',
        'themeType' => 'outline',
        'active' => false,
        'icon' => false,
        'arrow' => false,
    ];

    $renderer = new SchemaRenderer(new TransformRegistry);
    $attributes = $renderer->renderComponentAttributes($schema, $props);
    $structure = $renderer->renderStructure($schema, $props);

    expect(normalizeClassString($attributes['class'] ?? ''))->toBe(
        'c-button c-button--size-lg c-button--theme-outline-primary c-button--theme-primary'
    )
        ->and($attributes['class'] ?? '')->not->toContain('is-active')
        ->and($attributes['class'] ?? '')->not->toContain('has-icon')
        ->and($attributes['class'] ?? '')->not->toContain('has-arrow')
        ->and($attributes['class'] ?? '')->not->toContain('c-button--pill')
        ->and($structure)->not->toHaveKey('icon')
        ->and($structure)->not->toHaveKey('arrow')
        ->and($structure['label']['attributes']['class'] ?? '')->toBe('c-button__label c-button__label--size-lg');
});

it('ignores unknown class-rule modes and skips classes on naming modes', function () {
    $schema = [
        'schemaVersion' => '1.0',
        'name' => 'unknown-mode',
        'tag' => 'div',
        'classRules' => [
            ['classes' => 'keep', 'condition' => null],
            ['mode' => 'future-mode', 'classes' => 'should-not-apply', 'condition' => null],
        ],
    ];

    $attributes = (new SchemaRenderer(new TransformRegistry))
        ->renderComponentAttributes($schema, []);

    expect($attributes['class'] ?? '')->toBe('keep')
        ->and($attributes['class'] ?? '')->not->toContain('should-not-apply');
});
