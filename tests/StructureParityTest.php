<?php

use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;

function structureCases(): array
{
    return require __DIR__.'/Fixtures/structure-cases.php';
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function structureNode(
    string $path,
    ?string $tag,
    bool $fragment,
    ?array $slot,
    array $attributes,
    array $children = [],
    array $overrides = [],
): array {
    return array_merge([
        'path' => $path,
        'tag' => $tag,
        'fragment' => $fragment,
        'slot' => $slot,
        'component' => null,
        'attributes' => $attributes,
        'children' => $children,
    ], $overrides);
}

function expectedStructures(): array
{
    return [
        'section-default-slot' => [
            'content' => structureNode('content', null, true, ['name' => null, 'default' => true], ['class' => '']),
        ],
        'alert-nested-default-slot' => [
            'wrapper' => structureNode('wrapper', 'div', false, null, ['class' => 'flex items-start gap-x-3'], [
                'content' => structureNode('wrapper.content', 'div', false, ['name' => null, 'default' => true], ['class' => 'flex-1']),
                'icon' => structureNode('wrapper.icon', 'div', false, null, ['class' => 'leading-none'], [], [
                    'component' => [
                        'ref' => 'ui.icon',
                        'from' => 'type',
                        'map' => [
                            'info' => 'heroicon-o-information-circle',
                            'error' => 'heroicon-o-x-circle',
                        ],
                        'class' => 'size-7',
                    ],
                ]),
            ]),
        ],
        'card-named-slots' => [
            'body' => structureNode('body', 'div', false, null, ['class' => ''], [
                'inner' => structureNode('body.inner', 'div', false, null, ['class' => ''], [
                    'content' => structureNode('body.inner.content', 'div', false, ['name' => null, 'default' => true], ['class' => '']),
                    'footer' => structureNode('body.inner.footer', 'div', false, ['name' => 'footer', 'default' => false], ['class' => '']),
                    'header' => structureNode('body.inner.header', 'div', false, ['name' => 'header', 'default' => false], ['class' => '']),
                ]),
            ]),
            'image' => structureNode('image', 'div', false, ['name' => 'image', 'default' => false], ['class' => '']),
        ],
        'void-media' => [
            'break' => structureNode('break', 'br', false, null, ['class' => '']),
            'image' => structureNode('image', 'img', false, null, ['class' => 'block w-full']),
            'input' => structureNode('input', 'input', false, null, ['class' => '']),
        ],
        'component-ref-resolving' => [
            'mapped' => structureNode('mapped', 'div', false, null, ['class' => ''], [], [
                'component' => [
                    'ref' => 'ui.icon',
                    'from' => 'type',
                    'map' => [
                        'info' => 'heroicon-o-information-circle',
                        'error' => 'heroicon-o-x-circle',
                    ],
                    'class' => 'size-7',
                ],
            ]),
            'plain' => structureNode('plain', 'span', false, null, ['class' => ''], [], [
                'component' => [
                    'ref' => 'heroicon-o-chevron-down',
                    'props' => ['aria-hidden' => true],
                ],
            ]),
            'unmapped' => structureNode('unmapped', 'div', false, null, ['class' => ''], [], [
                'component' => [
                    'ref' => 'ui.icon',
                    'from' => 'type',
                    'map' => [
                        'info' => 'heroicon-o-information-circle',
                    ],
                    'class' => 'size-7',
                ],
            ]),
        ],
        'component-ref-missing-mapping' => [
            'mapped' => structureNode('mapped', 'div', false, null, ['class' => ''], [], [
                'component' => [
                    'ref' => 'ui.icon',
                    'from' => 'type',
                    'map' => [
                        'info' => 'heroicon-o-information-circle',
                        'error' => 'heroicon-o-x-circle',
                    ],
                    'class' => 'size-7',
                ],
            ]),
            'plain' => structureNode('plain', 'span', false, null, ['class' => ''], [], [
                'component' => [
                    'ref' => 'heroicon-o-chevron-down',
                    'props' => ['aria-hidden' => true],
                ],
            ]),
            'unmapped' => structureNode('unmapped', 'div', false, null, ['class' => ''], [], [
                'component' => [
                    'ref' => 'ui.icon',
                    'from' => 'type',
                    'map' => [
                        'info' => 'heroicon-o-information-circle',
                    ],
                    'class' => 'size-7',
                ],
            ]),
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
    if (! file_exists(dirname(__DIR__).'/dist/parity.js')) {
        $this->markTestSkipped('dist/parity.js not built');
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
