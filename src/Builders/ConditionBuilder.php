<?php

namespace Sprout\Builders;

final class ConditionBuilder
{
    public function __construct(
        private readonly string $prop,
        private readonly string $operator = 'truthy',
        private readonly mixed $value = null,
    ) {}

    public static function truthy(string $prop): self
    {
        return new self($prop, 'truthy');
    }

    public static function equals(string $prop, mixed $value): self
    {
        return new self($prop, 'equals', $value);
    }

    public static function notEquals(string $prop, mixed $value): self
    {
        return new self($prop, 'notEquals', $value);
    }

    public function toArray(): array
    {
        $condition = [
            'prop' => $this->prop,
            'operator' => $this->operator,
        ];

        if ($this->value !== null) {
            $condition['value'] = $this->value;
        }

        return $condition;
    }
}
