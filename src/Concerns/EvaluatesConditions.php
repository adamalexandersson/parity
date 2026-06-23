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

        $prop = $condition['prop'] ?? null;
        $operator = $condition['operator'] ?? 'truthy';
        $expected = $condition['value'] ?? null;

        if (! $prop) {
            return false;
        }

        $value = $this->resolveConditionValue($prop);

        return match ($operator) {
            'truthy' => $value !== null && $value !== false && $value !== '',
            'falsy' => $value === null || $value === false || $value === '',
            'equals', '==' => $value === $expected,
            'notEquals', '!=' => $value !== $expected,
            'any' => $this->evaluateAnyCondition($condition),
            'all' => $this->evaluateAllCondition($condition),
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
}
