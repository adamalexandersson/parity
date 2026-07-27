<?php

namespace Parity\Schema;

use Parity\Exceptions\SchemaException;

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

    public static function defaultSlotPath(array $schema): ?string
    {
        $paths = [];
        self::walkDefault($schema, '', $paths);

        if (count($paths) > 1) {
            throw new SchemaException(
                'Multiple default slot holders found: '.implode(', ', $paths)
            );
        }

        return $paths[0] ?? null;
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

    /**
     * @param  list<string>  $paths
     */
    private static function walkDefault(array $schema, string $parentPath, array &$paths): void
    {
        $slot = $schema['slot'] ?? null;

        if (is_array($slot) && ($slot['default'] ?? false) === true) {
            if ($parentPath !== '') {
                $paths[] = $parentPath;
            }
        }

        foreach ($schema['children'] ?? [] as $key => $child) {
            if (! is_array($child)) {
                continue;
            }

            $path = $parentPath === '' ? (string) $key : "{$parentPath}.{$key}";
            self::walkDefault($child, $path, $paths);
        }
    }
}
