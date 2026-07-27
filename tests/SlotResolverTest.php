<?php

use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;
use Parity\Render\SlotResolver;

it('collects expected default slot targets', function () {
    $cases = require __DIR__.'/Fixtures/structure-cases.php';
    $renderer = new SchemaRenderer(new TransformRegistry);

    foreach ($cases as $name => $case) {
        $structure = $renderer->renderStructure($case['schema'], $case['props'] ?? []);
        $defaultSlot = $case['schema']['defaultSlot'] ?? null;

        expect(SlotResolver::collectDefaultSlotTargets($structure, $defaultSlot))
            ->toBe($case['slotTargets'], "Slot targets mismatch for case {$name}");
    }
});

it('does not treat empty structure children as present', function () {
    $element = [
        'slot' => ['default' => true, 'name' => null],
        'children' => [],
    ];

    expect(SlotResolver::hasStructureChildren($element['children']))->toBeFalse()
        ->and(SlotResolver::shouldRenderDefaultSlot($element, 'content', 'content', 'content'))->toBeTrue();
});
