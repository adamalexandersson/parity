<?php

use Parity\Component;
use Parity\Node;

return Component::make('card', tag: 'div')
    ->children([
        Node::make('image', tag: 'div')->slot('image'),
        Node::make('body', tag: 'div')->children([
            Node::make('inner', tag: 'div')->children([
                Node::make('header', tag: 'div')->slot('header'),
                Node::make('content', tag: 'div')->slot(),
                Node::make('footer', tag: 'div')->slot('footer'),
            ]),
        ]),
    ])
    ->toSchema();
