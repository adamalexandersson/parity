<?php

namespace Sprout\Tests;

use PHPUnit\Framework\TestCase;
use Sprout\Component;
use Sprout\Node;
use Sprout\Schema\SlotCollector;

class SlotCollectorTest extends TestCase
{
    public function test_it_collects_named_slots_from_schema_tree(): void
    {
        $schema = Component::make('card', tag: 'div')
            ->slot('body.inner.content')
            ->children([
                Node::make('image', tag: 'div')->holdsNamedSlot('image'),
                Node::make('body', tag: 'div')->children([
                    Node::make('inner', tag: 'div')->children([
                        Node::make('header', tag: 'div')->holdsNamedSlot('header'),
                        Node::make('content', tag: 'div')->holdsDefaultSlot(),
                        Node::make('footer', tag: 'div')->holdsNamedSlot('footer'),
                    ]),
                ]),
                Node::make('after', tag: 'div')->holdsNamedSlot('after'),
            ])
            ->toSchema();

        $this->assertSame(
            ['image', 'header', 'footer', 'after'],
            SlotCollector::collect($schema)
        );
        $this->assertSame(
            ['image', 'header', 'footer', 'after'],
            $schema['namedSlots']
        );
    }
}
