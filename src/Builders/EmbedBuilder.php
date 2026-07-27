<?php

namespace Sprout\Builders;

use Sprout\Node;

/**
 * Fluent builder for nested component references.
 *
 * Returned by {@see Node::component()}.
 */
final class EmbedBuilder
{
    private ?string $ref;

    private ?string $mappingKey = null;

    /** @var array<string, string> */
    private array $valueMap = [];

    /** @var array<string, mixed> */
    private array $componentProps = [];

    private ?string $componentClass = null;

    public function __construct(
        private readonly Node $node,
        ?string $ref = null,
    ) {
        $this->ref = $ref;
        $node->registerOpenBuilder('component');
    }

    public function from(string $prop): self
    {
        $this->mappingKey = $prop;

        return $this;
    }

    /** @param array<string, string> $map */
    public function map(array $map): self
    {
        $this->valueMap = $map;

        return $this;
    }

    /** @param array<string, mixed> $props */
    public function props(array $props): self
    {
        $this->componentProps = $props;

        return $this;
    }

    public function class(string $class): self
    {
        $this->componentClass = $class;

        return $this;
    }

    public function end(): Node
    {
        $this->node->clearOpenBuilder('component');
        $this->node->setComponent([
            'ref' => $this->ref,
            'from' => $this->mappingKey,
            'map' => $this->valueMap,
            'class' => $this->componentClass,
            'props' => $this->componentProps,
        ]);

        return $this->node;
    }
}
