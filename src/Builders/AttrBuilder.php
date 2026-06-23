<?php

namespace Sprout\Builders;

use Sprout\Node;

final class AttrBuilder
{
    private ?string $source = null;

    private ?string $cast = null;

    private mixed $default = null;

    private ?array $condition = null;

    public function __construct(
        private readonly Node $node,
        private readonly string $name,
        private readonly mixed $staticValue = null,
    ) {}

    public function from(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function cast(string $cast): self
    {
        $this->cast = $cast;

        return $this;
    }

    public function default(mixed $default): self
    {
        $this->default = $default;

        return $this;
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

    public function end(): Node
    {
        $this->node->pushAttribute([
            'name' => $this->name,
            'value' => $this->staticValue,
            'source' => $this->source,
            'cast' => $this->cast ?? 'string',
            'default' => $this->default,
            'condition' => $this->condition,
        ]);

        return $this->node;
    }
}
