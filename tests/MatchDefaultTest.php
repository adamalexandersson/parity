<?php

namespace Sprout\Tests;

use PHPUnit\Framework\TestCase;
use Sprout\Component;
use Sprout\Render\SchemaRenderer;
use Sprout\Registries\TransformRegistry;

class MatchDefaultTest extends TestCase
{
    public function test_null_theme_color_matches_explicit_default_case(): void
    {
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

        $this->assertStringContainsString('bg-gray-100', $attributes['class'] ?? '');
    }

    public function test_bool_prop_matches_bool_case(): void
    {
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

        $this->assertStringContainsString('p-3 md:p-4', $attributes['class'] ?? '');
    }

    public function test_string_true_from_blade_matches_bool_case(): void
    {
        $schema = Component::make('card', tag: 'div')
            ->match('stretch')
                ->case(true)->classes('h-full')->end()
                ->end()
            ->toSchema();

        $renderer = new SchemaRenderer(new TransformRegistry);

        $attributes = $renderer->renderComponentAttributes($schema, [
            'stretch' => 'true',
        ]);

        $this->assertStringContainsString('h-full', $attributes['class'] ?? '');
    }

    public function test_integer_match_value_does_not_coerce_to_boolean(): void
    {
        $schema = Component::make('heading', tag: 'div')
            ->match('level')
                ->unlessProp('size')
                ->case(1)->classes('text-6xl')->end()
                ->case(2)->classes('text-4xl')->end()
                ->end()
            ->toSchema();

        $renderer = new SchemaRenderer(new TransformRegistry);

        $attributes = $renderer->renderComponentAttributes($schema, [
            'level' => 1,
            'size' => false,
        ]);

        $this->assertStringContainsString('text-6xl', $attributes['class'] ?? '');
        $this->assertStringNotContainsString('text-4xl', $attributes['class'] ?? '');
    }

    public function test_blade_string_one_matches_bool_case(): void
    {
        $schema = Component::make('card', tag: 'div')
            ->match('stretch')
                ->case(true)->classes('h-full')->end()
                ->end()
            ->toSchema();

        $renderer = new SchemaRenderer(new TransformRegistry);

        $attributes = $renderer->renderComponentAttributes($schema, [
            'stretch' => '1',
        ]);

        $this->assertStringContainsString('h-full', $attributes['class'] ?? '');
    }
}
