<?php

use Sprout\Component;
use Sprout\Node;
use Sprout\Schema\SlotCollector;

it('collects named slots from the schema tree', function () {
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

    expect(SlotCollector::collect($schema))->toBe(['image', 'header', 'footer', 'after'])
        ->and($schema['namedSlots'])->toBe(['image', 'header', 'footer', 'after']);
});
