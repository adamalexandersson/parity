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

        return TailwindMerge::instance()->merge(...$classes);
    }
}
