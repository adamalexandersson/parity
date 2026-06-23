<?php

use Sprout\Component;
use Sprout\Node;

return Component::make('link', tag: 'a')
    ->slot('content')
    ->linkable('link')
    ->classes('inline-flex items-center font-bold')
    ->match('themeColor')
        ->case('primary')->classes('text-primary-600')->end()
        ->case('default')->classes('text-gray-800')->end()
        ->end()
    ->match('size', 'hasArrow')
        ->case('md', 'true')->classes('leading-6 gap-x-1.5')->end()
        ->case('md', 'false')->classes('leading-6')->end()
        ->end()
    ->children([
        Node::make('content')->fragment()->holdsDefaultSlot(),
    ])
    ->toSchema();
