<?php

namespace Sprout\Render;

final class SlotResolver
{
    /** @param array<string, mixed> $children */
    public static function hasStructureChildren(array $children): bool
    {
        return $children !== [];
    }

    /** @param array<string, mixed> $element */
    public static function isSlotNode(array $element, string $path, string $key, ?string $slotElement): bool
    {
        $slot = $element['slot'] ?? null;
        $isDefaultSlot = is_array($slot) && ($slot['default'] ?? false);

        return $isDefaultSlot || $path === $slotElement || $key === $slotElement;
    }

    /** @param array<string, mixed> $element */
    public static function shouldRenderDefaultSlot(array $element, string $path, string $key, ?string $slotElement): bool
    {
        if (! empty($element['richText'])) {
            return false;
        }

        $children = $element['children'] ?? [];

        if (! is_array($children)) {
            $children = [];
        }

        return ! self::hasStructureChildren($children)
            && self::isSlotNode($element, $path, $key, $slotElement);
    }

    /** @param array<string, mixed> $element @param array<string, mixed> $namedSlotProps */
    public static function shouldSkipNamedSlotNode(array $element, array $namedSlotProps): bool
    {
        $slot = $element['slot'] ?? null;
        $slotName = is_array($slot) ? ($slot['name'] ?? null) : null;

        if ($slotName === null || $slotName === '') {
            return false;
        }

        return ! array_key_exists($slotName, $namedSlotProps) || $namedSlotProps[$slotName] === null;
    }

    /**
     * @param array<string, mixed> $structure Built structure tree from SchemaRenderer
     * @return list<string>
     */
    public static function collectDefaultSlotTargets(array $structure, ?string $slotElement): array
    {
        $targets = [];

        foreach ($structure as $key => $element) {
            if (! is_array($element)) {
                continue;
            }

            $path = (string) ($element['path'] ?? $key);

            if (self::shouldRenderDefaultSlot($element, $path, (string) $key, $slotElement)) {
                $targets[] = $path;
            }

            $targets = array_merge(
                $targets,
                self::collectDefaultSlotTargets($element['children'] ?? [], $slotElement)
            );
        }

        return $targets;
    }
}
