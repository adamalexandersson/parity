<?php

namespace Sprout\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Sprout\Component;
use Sprout\Render\SchemaRenderer;
use Sprout\Registries\TransformRegistry;
use Sprout\Support\ClassFactory;

class CommonNestedMatchTest extends TestCase
{
    protected function setUp(): void
    {
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
    }

    public function test_include_common_applies_nested_companion_map(): void
    {
        $schema = Component::make('container', tag: 'div')
            ->includeCommon('verticalSpacing')
            ->toSchema();

        $renderer = new SchemaRenderer(new TransformRegistry);
        $classes = new ClassFactory;

        $reflection = new \ReflectionClass($renderer);
        $method = $reflection->getMethod('applyCommonMatch');
        $method->setAccessible(true);

        $method->invoke($renderer, [
            'common' => 'verticalSpacing',
            'props' => ['verticalSpacing'],
            'condition' => null,
        ], ['verticalSpacing' => 4], $classes);

        $this->assertStringContainsString('space-y-4', $classes->get());
        $this->assertStringContainsString('space-nested-y-4', $classes->get());
    }
}
