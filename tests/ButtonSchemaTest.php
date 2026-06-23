<?php

namespace Sprout\Tests;

use PHPUnit\Framework\TestCase;
use Sprout\Render\SchemaRenderer;
use Sprout\Registries\TransformRegistry;

class ButtonSchemaTest extends TestCase
{
    public function test_it_renders_button_classes_from_schema(): void
    {
        $schema = require __DIR__.'/fixtures/button.php';
        $renderer = new SchemaRenderer(new TransformRegistry);

        $attributes = $renderer->renderComponentAttributes($schema, [
            'size' => 'sm',
            'themeColor' => 'primary',
            'themeType' => 'solid',
            'pill' => 'true',
            'arrow' => true,
        ], 'button');

        $this->assertStringContainsString('inline-flex', $attributes['class']);
        $this->assertStringContainsString('px-4 py-2 text-sm', $attributes['class']);
        $this->assertStringContainsString('bg-primary-500 text-white', $attributes['class']);
        $this->assertStringContainsString('rounded-full', $attributes['class']);
        $this->assertSame('button', $attributes['data-component']);
    }
}
