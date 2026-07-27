<?php

namespace Parity\Support;

final class InlineStyleFactory
{
    private array $styles = [];

    public function add(string $property, string $value): self
    {
        if ($property !== '' && $value !== '') {
            $this->styles[$property] = $value;
        }

        return $this;
    }

    public function get(): string
    {
        if ($this->styles === []) {
            return '';
        }

        $parts = [];

        foreach ($this->styles as $property => $value) {
            $parts[] = "{$property}: {$value}";
        }

        return implode('; ', $parts);
    }

    public function toArray(): array
    {
        return $this->styles;
    }
}
