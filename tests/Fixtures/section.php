<?php

use Parity\Component;
use Parity\Node;

return Component::make('section', tag: 'section')
    ->classes('relative overflow-x-clip')
    ->children([
        Node::make('content')->fragment()->slot(),
    ])
    ->toSchema();
