<?php

namespace Sprout;

use Sprout\Schema\SlotCollector;

final class Component extends Node
{
    protected ?string $name = null;

    protected ?array $linkable = null;

    public static function make(string $name, ?string $tag = 'div'): static
    {
        $component = new self;
        $component->name = $name;
        $component->key = $name;
        $component->tag = $tag;

        return $component;
    }

    public function linkable(string $prop = 'link', string $tag = 'a'): self
    {
        $this->linkable = [
            'prop' => $prop,
            'tag' => $tag,
        ];

        return $this;
    }

    public function toSchema(): array
    {
        $schema = parent::toSchema();
        $schema['name'] = $this->name;

        if ($this->linkable !== null) {
            $schema['linkable'] = $this->linkable;
        }

        $defaultSlot = SlotCollector::defaultSlotPath($schema);

        if ($defaultSlot !== null) {
            $schema['defaultSlot'] = $defaultSlot;
        }

        $schema['namedSlots'] = SlotCollector::collect($schema);

        return $schema;
    }
}
