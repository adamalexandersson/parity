<?php

namespace Sprout\Schema;

final class SlotCollector
{
    /**
     * @return list<string>
     */
    public static function collect(array $schema): array
    {
        $names = [];

        self::walk($schema, $names);

        return array_values(array_unique($names));
    }

    /**
     * @param  list<string>  $names
     */
    private static function walk(array $schema, array &$names): void
    {
        $slot = $schema['slot'] ?? null;

        if (is_array($slot) && ! empty($slot['name']) && empty($slot['default'])) {
            $names[] = (string) $slot['name'];
        }

        foreach ($schema['children'] ?? [] as $child) {
            if (is_array($child)) {
                self::walk($child, $names);
            }
        }
    }
}
