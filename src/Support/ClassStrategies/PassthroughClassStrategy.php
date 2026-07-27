<?php

namespace Parity\Support\ClassStrategies;

use Parity\Contracts\ClassStrategy;

final class PassthroughClassStrategy implements ClassStrategy
{
    public function merge(array $classes): string
    {
        $unique = [];

        foreach ($classes as $class) {
            if ($class === '' || in_array($class, $unique, true)) {
                continue;
            }

            $unique[] = $class;
        }

        return implode(' ', $unique);
    }
}
