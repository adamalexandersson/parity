<?php

namespace Sprout\Tests;

use PHPUnit\Framework\TestCase;
use Sprout\Render\SchemaRenderer;
use Sprout\Registries\TransformRegistry;

class StructureParityTest extends TestCase
{
    /** @return array<string, array<string, mixed>> */
    protected function cases(): array
    {
        return require __DIR__.'/fixtures/structure-cases.php';
    }

    public function test_php_structure_snapshots_are_stable(): void
    {
        $renderer = new SchemaRenderer(new TransformRegistry);

        foreach ($this->cases() as $name => $case) {
            $structure = $renderer->renderStructure($case['schema'], $case['props'] ?? []);

            $this->assertSame(
                $this->expectedStructures()[$name],
                $this->normalizeStructure($structure),
                "Structure snapshot mismatch for case {$name}"
            );
        }
    }

    public function test_js_structure_and_slot_targets_match_php(): void
    {
        if (! file_exists(dirname(__DIR__).'/dist/sprout.js')) {
            $this->markTestSkipped('dist/sprout.js not built');
        }

        $script = dirname(__DIR__).'/scripts/structure-parity.mjs';

        if (! file_exists($script)) {
            $this->markTestSkipped('Structure parity script missing');
        }

        $phpRenderer = new SchemaRenderer(new TransformRegistry);
        $cases = $this->cases();
        $input = json_encode($cases, JSON_THROW_ON_ERROR);
        $command = sprintf('node %s', escapeshellarg($script));

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__));

        if (! is_resource($process)) {
            $this->fail('Unable to start structure parity Node process');
        }

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $jsResults = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        foreach ($cases as $name => $case) {
            $phpStructure = $this->normalizeStructure(
                $phpRenderer->renderStructure($case['schema'], $case['props'] ?? [])
            );

            $this->assertSame(
                $phpStructure,
                $jsResults[$name]['structure'] ?? [],
                "PHP/JS structure mismatch for case {$name}"
            );

            $this->assertSame(
                $case['slotTargets'],
                $jsResults[$name]['slotTargets'] ?? [],
                "PHP/JS slot target mismatch for case {$name}"
            );
        }
    }

    /** @param array<string, mixed> $structure */
    protected function normalizeStructure(array $structure): array
    {
        ksort($structure);

        $normalized = [];

        foreach ($structure as $key => $node) {
            $attributes = $node['attributes'] ?? [];
            $children = $node['children'] ?? [];

            $normalized[$key] = [
                'path' => $node['path'] ?? null,
                'tag' => $node['tag'] ?? null,
                'fragment' => (bool) ($node['fragment'] ?? false),
                'slot' => $node['slot'] ?? null,
                'attributes' => [
                    'class' => $attributes['class'] ?? $attributes['className'] ?? '',
                ],
                'children' => $this->normalizeStructure(is_array($children) ? $children : []),
            ];
        }

        return $normalized;
    }

    /** @return array<string, array<string, mixed>> */
    protected function expectedStructures(): array
    {
        return [
            'section-default-slot' => [
                'content' => [
                    'path' => 'content',
                    'tag' => null,
                    'fragment' => true,
                    'slot' => ['name' => null, 'default' => true],
                    'attributes' => ['class' => ''],
                    'children' => [],
                ],
            ],
            'alert-nested-default-slot' => [
                'wrapper' => [
                    'path' => 'wrapper',
                    'tag' => 'div',
                    'fragment' => false,
                    'slot' => null,
                    'attributes' => ['class' => 'flex items-start gap-x-3'],
                    'children' => [
                        'content' => [
                            'path' => 'wrapper.content',
                            'tag' => 'div',
                            'fragment' => false,
                            'slot' => ['name' => null, 'default' => true],
                            'attributes' => ['class' => 'flex-1'],
                            'children' => [],
                        ],
                        'icon' => [
                            'path' => 'wrapper.icon',
                            'tag' => 'div',
                            'fragment' => false,
                            'slot' => null,
                            'attributes' => ['class' => 'leading-none'],
                            'children' => [],
                        ],
                    ],
                ],
            ],
            'card-named-slots' => [
                'body' => [
                    'path' => 'body',
                    'tag' => 'div',
                    'fragment' => false,
                    'slot' => null,
                    'attributes' => ['class' => ''],
                    'children' => [
                        'inner' => [
                            'path' => 'body.inner',
                            'tag' => 'div',
                            'fragment' => false,
                            'slot' => null,
                            'attributes' => ['class' => ''],
                            'children' => [
                                'content' => [
                                    'path' => 'body.inner.content',
                                    'tag' => 'div',
                                    'fragment' => false,
                                    'slot' => ['name' => null, 'default' => true],
                                    'attributes' => ['class' => ''],
                                    'children' => [],
                                ],
                                'footer' => [
                                    'path' => 'body.inner.footer',
                                    'tag' => 'div',
                                    'fragment' => false,
                                    'slot' => ['name' => 'footer', 'default' => false],
                                    'attributes' => ['class' => ''],
                                    'children' => [],
                                ],
                                'header' => [
                                    'path' => 'body.inner.header',
                                    'tag' => 'div',
                                    'fragment' => false,
                                    'slot' => ['name' => 'header', 'default' => false],
                                    'attributes' => ['class' => ''],
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                'image' => [
                    'path' => 'image',
                    'tag' => 'div',
                    'fragment' => false,
                    'slot' => ['name' => 'image', 'default' => false],
                    'attributes' => ['class' => ''],
                    'children' => [],
                ],
            ],
        ];
    }
}
