<?php

namespace Parity\Schema;

final class Version
{
    public const CURRENT = '1.0';

    /**
     * Extract the major segment from a schema version string (e.g. "1.0" → "1").
     */
    public static function major(string $version): string
    {
        $segment = explode('.', $version, 2)[0] ?? '';

        return $segment !== '' ? $segment : $version;
    }

    /**
     * True when the given version shares CURRENT's major.
     */
    public static function isCompatible(string $version): bool
    {
        return self::major($version) === self::major(self::CURRENT);
    }
}
