<?php

namespace Parity\Builders;

use Parity\Node;

final class StyleBuilder
{
    private ?string $source = null;

    private ?string $cast = null;

    private mixed $default = null;

    private ?array $condition = null;

    private bool $cssUrl = false;

    public function __construct(
        private readonly Node $node,
        private readonly string $property,
    ) {
        $node->registerOpenBuilder('style');
    }

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

    public function asCssUrl(): self
    {
        $this->cssUrl = true;

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
        $style = [
            'property' => $this->property,
            'source' => $this->source,
            'cast' => $this->cast ?? 'string',
            'default' => $this->default,
            'condition' => $this->condition,
        ];

        if ($this->cssUrl) {
            $style['cssUrl'] = true;
        }

        $this->node->clearOpenBuilder('style');
        $this->node->pushStyle($style);

        return $this->node;
    }
}
