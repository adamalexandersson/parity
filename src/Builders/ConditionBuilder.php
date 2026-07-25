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

    public static function in(string $prop, array $value): self
    {
        return new self($prop, 'in', $value);
    }

    public static function notIn(string $prop, array $value): self
    {
        return new self($prop, 'notIn', $value);
    }

    public static function gt(string $prop, mixed $value): self
    {
        return new self($prop, 'gt', $value);
    }

    public static function gte(string $prop, mixed $value): self
    {
        return new self($prop, 'gte', $value);
    }

    public static function lt(string $prop, mixed $value): self
    {
        return new self($prop, 'lt', $value);
    }

    public static function lte(string $prop, mixed $value): self
    {
        return new self($prop, 'lte', $value);
    }

    public static function contains(string $prop, mixed $value): self
    {
        return new self($prop, 'contains', $value);
    }

    public static function empty(string $prop): self
    {
        return new self($prop, 'empty');
    }

    public static function notEmpty(string $prop): self
    {
        return new self($prop, 'notEmpty');
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
