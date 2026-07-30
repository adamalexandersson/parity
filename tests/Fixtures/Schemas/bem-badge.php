<?php

use Parity\Component;
use Parity\Node;

return Component::make('status-badge')
    ->block('badge')
    ->category('component')
    ->modifier('pill')
    ->modifier('size')
    ->modifier('themeColor', 'theme')
    ->modifier(['themeType', 'themeColor'], 'theme')
    ->is('active')
    ->has('icon')
    ->children([
        Node::make('content', tag: 'span')
            ->element('label')
            ->modifier('size')
            ->slot(),
    ])
    ->toSchema();
