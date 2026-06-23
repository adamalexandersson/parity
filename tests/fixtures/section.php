<?php

use Sprout\Component;
use Sprout\Node;

return Component::make('section', tag: 'section')
    ->slot('content')
    ->classes('relative overflow-x-clip')
    ->children([
        Node::make('content')->fragment()->holdsDefaultSlot(),
    ])
    ->toSchema();
