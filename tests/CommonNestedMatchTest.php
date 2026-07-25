<?php

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Sprout\Component;
use Sprout\Registries\TransformRegistry;
use Sprout\Render\SchemaRenderer;
use Sprout\Support\ClassFactory;

beforeEach(function () {
    $container = Container::getInstance();

    if (! $container->bound('config')) {
        $container->instance('config', new Repository([]));
    }

    config([
        'sprout.common' => [
            'verticalSpacing' => [
                4 => 'space-y-4',
            ],
            'verticalSpacingNested' => [
                4 => 'space-nested-y-4',
            ],
        ],
    ]);
});

it('applies nested companion map via includeCommon', function () {
    $schema = Component::make('container', tag: 'div')
        ->includeCommon('verticalSpacing')
        ->toSchema();

    $renderer = new SchemaRenderer(new TransformRegistry);
    $classes = new ClassFactory;

    $reflection = new ReflectionClass($renderer);
    $method = $reflection->getMethod('applyCommonMatch');
    $method->setAccessible(true);

    $method->invoke($renderer, [
        'common' => 'verticalSpacing',
        'props' => ['verticalSpacing'],
        'condition' => null,
    ], ['verticalSpacing' => 4], $classes);

    expect($classes->get())->toContain('space-y-4')
        ->and($classes->get())->toContain('space-nested-y-4');
});
