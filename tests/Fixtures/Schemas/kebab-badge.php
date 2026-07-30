<?php

use Parity\Component;
use Parity\Node;

return Component::make('badge')
    ->variant('pill')
    ->variant('size')
    ->variant('themeColor')
    ->is('active')
    ->children([
        Node::make('content', tag: 'span')
            ->element('label')
            ->variant('size')
            ->slot(),
    ])
    ->toSchema();
