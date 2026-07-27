<?php

namespace Parity\Builders;

use Parity\Node;

final class MatchBuilder
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $caseOutcomes = [];

    /** @var list<array<string, mixed>>|null */
    private ?array $defaultOutcomes = null;

    /** @var list<string> */
    private array $caseKeys = [];

    private ?array $condition = null;

    public function __construct(
        private readonly Node $node,
        private readonly array $props,
    ) {
        $node->registerOpenBuilder('match');
    }

    public function when(string $prop, mixed $value = null): self
    {
        $this->condition = $value === null
            ? ConditionBuilder::truthy($prop)->toArray()
            : ConditionBuilder::equals($prop, $value)->toArray();

        return $this;
    }

    public function unless(string $prop, mixed $value = null): self
    {
        $this->condition = $value === null
            ? ['prop' => $prop, 'operator' => 'falsy']
            : ConditionBuilder::notEquals($prop, $value)->toArray();

        return $this;
    }

    public function case(string|bool|int|null ...$values): OutcomeContext
    {
        $key = implode("\0", array_map(fn ($v) => $this->normalizeValue($v), $values));
        $this->caseKeys[] = $key;

        return new OutcomeContext($this, $key);
    }

    public function default(): OutcomeContext
    {
        return new OutcomeContext($this, '__default__');
    }

    public function end(): Node
    {
        $cases = [];

        foreach ($this->caseKeys as $index => $key) {
            $values = explode("\0", $key);

            $cases[] = [
                'values' => $values,
                'outcomes' => $this->caseOutcomes[$key] ?? [],
            ];
        }

        $this->node->clearOpenBuilder('match');
        $this->node->pushMatch([
            'props' => $this->props,
            'cases' => $cases,
            'default' => $this->defaultOutcomes,
            'condition' => $this->condition,
        ]);

        return $this->node;
    }

    /** @param list<array<string, mixed>> $outcomes */
    public function commitOutcomes(string $target, array $outcomes): void
    {
        if ($target === '__default__') {
            $this->defaultOutcomes = $outcomes;

            return;
        }

        $this->caseOutcomes[$target] = $outcomes;
    }

    private function normalizeValue(string|bool|int|null $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '';
        }

        return (string) $value;
    }
}
