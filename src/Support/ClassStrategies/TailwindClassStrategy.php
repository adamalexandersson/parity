<?php

namespace Sprout\Support\ClassStrategies;

use Sprout\Contracts\ClassStrategy;
use TailwindMerge\TailwindMerge;

final class TailwindClassStrategy implements ClassStrategy
{
    public function merge(array $classes): string
    {
        if ($classes === []) {
            return '';
        }

        if (! class_exists(TailwindMerge::class)) {
            throw new \RuntimeException(
                'The "tailwind" class strategy requires gehrisandro/tailwind-merge-php. '
                .'Run `composer require gehrisandro/tailwind-merge-php` or set '
                .'config(\'sprout.classes.strategy\') to \'passthrough\'.'
            );
        }

        return TailwindMerge::instance()->merge(...$classes);
    }
}
