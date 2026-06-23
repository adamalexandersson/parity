<?php

namespace Sprout\Support;

final class AttributeFactory
{
    private array $attributes = [];

    public function add(string $attr = '', mixed $value = ''): void
    {
        if ($attr === '') {
            return;
        }

        if (is_array($value) || is_object($value)) {
            $value = htmlspecialchars(json_encode($value), ENT_QUOTES, 'UTF-8');
        }

        $this->attributes[$attr] = $value;
    }

    public function remove(string $attr): void
    {
        unset($this->attributes[$attr]);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
