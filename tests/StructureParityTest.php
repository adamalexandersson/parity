<?php

use Sprout\Registries\TransformRegistry;
use Sprout\Render\SchemaRenderer;

function structureCases(): array
{
    return require __DIR__.'/fixtures/structure-cases.php';
}

function expectedStructures(): array
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
        'void-media' => [
            'break' => [
                'path' => 'break',
                'tag' => 'br',
                'fragment' => false,
                'slot' => null,
                'attributes' => ['class' => ''],
                'children' => [],
            ],
            'image' => [
                'path' => 'image',
                'tag' => 'img',
                'fragment' => false,
                'slot' => null,
                'attributes' => ['class' => 'block w-full'],
                'children' => [],
            ],
            'input' => [
                'path' => 'input',
                'tag' => 'input',
                'fragment' => false,
                'slot' => null,
                'attributes' => ['class' => ''],
                'children' => [],
            ],
        ],
    ];
}

it('keeps php structure snapshots stable', function () {
    $renderer = new SchemaRenderer(new TransformRegistry);

    foreach (structureCases() as $name => $case) {
        $structure = $renderer->renderStructure($case['schema'], $case['props'] ?? []);

        expect(normalizeStructure($structure))
            ->toBe(expectedStructures()[$name], "Structure snapshot mismatch for case {$name}");
    }
});

it('matches php and js structure and slot targets', function () {
    if (! file_exists(dirname(__DIR__).'/dist/sprout.js')) {
        $this->markTestSkipped('dist/sprout.js not built');
    }

    $script = dirname(__DIR__).'/scripts/structure-parity.mjs';

    if (! file_exists($script)) {
        $this->markTestSkipped('Structure parity script missing');
    }

    $phpRenderer = new SchemaRenderer(new TransformRegistry);
    $cases = structureCases();
    $jsResults = runNodeParityScript($script, $cases);

    foreach ($cases as $name => $case) {
        $phpStructure = normalizeStructure(
            $phpRenderer->renderStructure($case['schema'], $case['props'] ?? [])
        );

        expect($jsResults[$name]['structure'] ?? [])->toBe($phpStructure, "PHP/JS structure mismatch for case {$name}")
            ->and($jsResults[$name]['slotTargets'] ?? [])->toBe($case['slotTargets'], "PHP/JS slot target mismatch for case {$name}");
    }
});
