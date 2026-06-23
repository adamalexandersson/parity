<?php

namespace Sprout\Tests;

use PHPUnit\Framework\TestCase;
use Sprout\Render\SchemaRenderer;
use Sprout\Registries\TransformRegistry;

class StyleCssUrlTest extends TestCase
{
    public function test_background_image_style_wraps_resolved_url_in_css_url(): void
    {
        $transforms = new TransformRegistry;
        $transforms->register('imageUrl', fn ($value) => is_string($value) ? $value : null);

        $renderer = new SchemaRenderer($transforms);

        $schema = [
            'schemaVersion' => '1.0',
            'styles' => [
                [
                    'property' => 'background-image',
                    'source' => 'backgroundImage',
                    'cast' => 'imageUrl',
                    'cssUrl' => true,
                    'condition' => ['prop' => 'backgroundImage', 'operator' => 'truthy'],
                ],
            ],
        ];

        $attributes = $renderer->renderComponentAttributes(
            $schema,
            ['backgroundImage' => 'https://picsum.photos/1920/800'],
            'section'
        );

        $this->assertSame(
            'background-image: url(https://picsum.photos/1920/800)',
            $attributes['style'] ?? ''
        );
    }

    public function test_css_url_does_not_double_wrap_existing_url_syntax(): void
    {
        $renderer = new SchemaRenderer(new TransformRegistry);

        $schema = [
            'schemaVersion' => '1.0',
            'styles' => [
                [
                    'property' => 'background-image',
                    'source' => 'backgroundImage',
                    'cast' => 'string',
                    'cssUrl' => true,
                    'condition' => ['prop' => 'backgroundImage', 'operator' => 'truthy'],
                ],
            ],
        ];

        $attributes = $renderer->renderComponentAttributes(
            $schema,
            ['backgroundImage' => 'url(https://example.com/image.jpg)'],
            'section'
        );

        $this->assertSame(
            'background-image: url(https://example.com/image.jpg)',
            $attributes['style'] ?? ''
        );
    }
}
