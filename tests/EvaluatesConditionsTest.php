<?php

use Sprout\Concerns\EvaluatesConditions;

class ConditionHarness
{
    use EvaluatesConditions;

    /** @param  array<string, mixed>  $props */
    public function __construct(private array $props = []) {}

    public function check(array|string|null $condition): bool
    {
        return $this->evaluateCondition($condition);
    }

    protected function resolveConditionValue(string $prop): mixed
    {
        $parts = explode('.', $prop);
        $value = $this->props;

        foreach ($parts as $part) {
            if (! is_array($value) || ! array_key_exists($part, $value)) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }
}

it('evaluates any without requiring a prop', function () {
    $harness = new ConditionHarness(['arrow' => false, 'icon' => true]);

    expect($harness->check([
        'operator' => 'any',
        'conditions' => [
            ['prop' => 'arrow', 'operator' => 'truthy'],
            ['prop' => 'icon', 'operator' => 'truthy'],
        ],
    ]))->toBeTrue();
});

it('evaluates all without requiring a prop', function () {
    $harness = new ConditionHarness(['href' => 'https://example.com', 'external' => false]);

    expect($harness->check([
        'operator' => 'all',
        'conditions' => [
            ['prop' => 'href', 'operator' => 'truthy'],
            ['prop' => 'external', 'operator' => 'falsy'],
        ],
    ]))->toBeTrue();
});

it('returns false for all when a nested condition fails', function () {
    $harness = new ConditionHarness(['href' => 'https://example.com', 'external' => true]);

    expect($harness->check([
        'operator' => 'all',
        'conditions' => [
            ['prop' => 'href', 'operator' => 'truthy'],
            ['prop' => 'external', 'operator' => 'falsy'],
        ],
    ]))->toBeFalse();
});
