<?php

namespace Sprout\Exceptions;

use RuntimeException;

class SchemaException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $component = null,
        public readonly ?string $path = null,
    ) {
        $parts = array_filter([
            $component ? "[{$component}]" : null,
            $path ? "{$path}:" : null,
            $message,
        ]);

        parent::__construct(implode(' ', $parts));
    }
}
