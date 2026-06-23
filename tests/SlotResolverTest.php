<?php

namespace Sprout\Tests;

use PHPUnit\Framework\TestCase;
use Sprout\Render\SchemaRenderer;
use Sprout\Render\SlotResolver;
use Sprout\Registries\TransformRegistry;

class SlotResolverTest extends TestCase
{
    /** @return array<string, array<string, mixed>> */
    protected function cases(): array
    {
        return require __DIR__.'/fixtures/structure-cases.php';
    }

    public function test_collects_expected_default_slot_targets(): void
    {
        $renderer = new SchemaRenderer(new TransformRegistry);

        foreach ($this->cases() as $name => $case) {
            $structure = $renderer->renderStructure($case['schema'], $case['props'] ?? []);
            $defaultSlot = $case['schema']['defaultSlot'] ?? null;

            $this->assertSame(
                $case['slotTargets'],
                SlotResolver::collectDefaultSlotTargets($structure, $defaultSlot),
                "Slot targets mismatch for case {$name}"
            );
        }
    }

    public function test_empty_structure_children_are_not_treated_as_present(): void
    {
        $element = [
            'slot' => ['default' => true, 'name' => null],
            'children' => [],
        ];

        $this->assertFalse(SlotResolver::hasStructureChildren($element['children']));
        $this->assertTrue(
            SlotResolver::shouldRenderDefaultSlot($element, 'content', 'content', 'content')
        );
    }
}
