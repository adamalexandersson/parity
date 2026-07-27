<?php

use Parity\Component;
use Parity\Node;
use Parity\Schema\SlotCollector;

it('collects named slots from the schema tree', function () {
    $schema = Component::make('card', tag: 'div')
        ->children([
            Node::make('image', tag: 'div')->slot('image'),
            Node::make('body', tag: 'div')->children([
                Node::make('inner', tag: 'div')->children([
                    Node::make('header', tag: 'div')->slot('header'),
                    Node::make('content', tag: 'div')->slot(),
                    Node::make('footer', tag: 'div')->slot('footer'),
                ]),
            ]),
            Node::make('after', tag: 'div')->slot('after'),
        ])
        ->toSchema();

    expect(SlotCollector::collect($schema))->toBe(['image', 'header', 'footer', 'after'])
        ->and($schema['namedSlots'])->toBe(['image', 'header', 'footer', 'after'])
        ->and($schema['defaultSlot'])->toBe('body.inner.content');
});
