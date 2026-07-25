<?php

namespace Sprout\Concerns;

trait EvaluatesConditions
{
    abstract protected function resolveConditionValue(string $prop): mixed;

    protected function evaluateCondition(array|string|null $condition): bool
    {
        if ($condition === null) {
            return true;
        }

        if (is_string($condition)) {
            $value = $this->resolveConditionValue($condition);

            return $value !== null && $value !== false && $value !== '';
        }

        $operator = $condition['operator'] ?? 'truthy';

        if ($operator === 'any') {
            return $this->evaluateAnyCondition($condition);
        }

        if ($operator === 'all') {
            return $this->evaluateAllCondition($condition);
        }

        $prop = $condition['prop'] ?? null;

        if (! $prop) {
            return false;
        }

        $value = $this->resolveConditionValue($prop);
        $expected = $condition['value'] ?? null;

        return match ($operator) {
            'truthy' => $value !== null && $value !== false && $value !== '',
            'falsy' => $value === null || $value === false || $value === '',
            'equals', '==' => $value === $expected,
            'notEquals', '!=' => $value !== $expected,
            'in' => is_array($expected) && in_array($value, $expected, true),
            'notIn' => is_array($expected) && ! in_array($value, $expected, true),
            'gt', 'gte', 'lt', 'lte' => $this->compareNumeric($operator, $value, $expected),
            'contains' => $this->evaluateContains($value, $expected),
            'empty' => $this->isEmptyValue($value),
            'notEmpty' => ! $this->isEmptyValue($value),
            default => false,
        };
    }

    protected function evaluateAnyCondition(array $condition): bool
    {
        foreach ($condition['conditions'] ?? [] as $sub) {
            if ($this->evaluateCondition($sub)) {
                return true;
            }
        }

        return false;
    }

    protected function evaluateAllCondition(array $condition): bool
    {
        foreach ($condition['conditions'] ?? [] as $sub) {
            if (! $this->evaluateCondition($sub)) {
                return false;
            }
        }

        return true;
    }

    protected function compareNumeric(string $operator, mixed $value, mixed $expected): bool
    {
        if (! is_numeric($value) || ! is_numeric($expected)) {
            return false;
        }

        $left = (float) $value;
        $right = (float) $expected;

        return match ($operator) {
            'gt' => $left > $right,
            'gte' => $left >= $right,
            'lt' => $left < $right,
            'lte' => $left <= $right,
            default => false,
        };
    }

    protected function evaluateContains(mixed $value, mixed $expected): bool
    {
        if (is_array($value)) {
            return in_array($expected, $value, true);
        }

        if (is_string($value) && (is_string($expected) || is_numeric($expected))) {
            return str_contains($value, (string) $expected);
        }

        return false;
    }

    protected function isEmptyValue(mixed $value): bool
    {
        return $value === null
            || $value === false
            || $value === ''
            || $value === []
            || (is_string($value) && trim($value) === '');
    }
}
