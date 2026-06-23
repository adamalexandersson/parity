<?php

namespace Sprout\Builders;

use Sprout\Node;

final class OutcomeContext
{
    /** @var list<array<string, mixed>> */
    private array $outcomes = [];

    public function __construct(
        private readonly MatchBuilder $matchBuilder,
        private readonly string $target,
    ) {}

    public function classes(string $classes): self
    {
        $this->outcomes[] = ['type' => 'classes', 'value' => $classes];

        return $this;
    }

    public function attr(string $name, mixed $value): self
    {
        $this->outcomes[] = ['type' => 'attr', 'name' => $name, 'value' => $value];

        return $this;
    }

    public function style(string $property, string $value): self
    {
        $this->outcomes[] = ['type' => 'style', 'property' => $property, 'value' => $value];

        return $this;
    }

    public function end(): MatchBuilder
    {
        $this->matchBuilder->commitOutcomes($this->target, $this->outcomes);

        return $this->matchBuilder;
    }
}
