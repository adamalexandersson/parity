<?php

namespace Parity\Support\ClassStrategies;

use Parity\Contracts\ClassStrategy;
use TailwindMerge\TailwindMerge;

final class TailwindClassStrategy implements ClassStrategy
{
    /**
     * TailwindMerge::instance() rebuilds the whole merged config on every call,
     * so the instance is shared for the lifetime of the process.
     */
    private static ?TailwindMerge $merger = null;

    /**
     * Nested components repeat the same class combinations constantly, so
     * results are memoized by input. Bounded to avoid unbounded growth.
     *
     * @var array<string, string>
     */
    private static array $cache = [];

    private const CACHE_LIMIT = 2000;

    public function merge(array $classes): string
    {
        if ($classes === []) {
            return '';
        }

        $key = implode(' ', $classes);

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $merged = $this->merger()->merge($classes);

        if (count(self::$cache) >= self::CACHE_LIMIT) {
            self::$cache = [];
        }

        return self::$cache[$key] = $merged;
    }

    private function merger(): TailwindMerge
    {
        if (self::$merger instanceof TailwindMerge) {
            return self::$merger;
        }

        if (! class_exists(TailwindMerge::class)) {
            throw new \RuntimeException(
                'The "tailwind" class strategy requires gehrisandro/tailwind-merge-php. '
                .'Run `composer require gehrisandro/tailwind-merge-php` or set '
                .'config(\'parity.classes.strategy\') to \'passthrough\'.'
            );
        }

        return self::$merger = TailwindMerge::instance();
    }

    /**
     * Reset shared state (tests, long-running workers with changed config).
     */
    public static function flush(): void
    {
        self::$merger = null;
        self::$cache = [];
    }
}
