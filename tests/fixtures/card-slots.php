<?php

use Sprout\Component;
use Sprout\Node;

return Component::make('card', tag: 'div')
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
    ])
    ->toSchema();
