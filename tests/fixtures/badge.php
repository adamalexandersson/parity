<?php

use Sprout\Component;
use Sprout\Node;

return Component::make('badge', tag: 'span')
    ->slot('content')
    ->classes('inline-flex items-center justify-center font-medium')
    ->match('pill')
        ->case('true')->classes('rounded-full')->end()
        ->case('false')->classes('rounded-md')->end()
        ->end()
    ->match('equilateral', 'size')
        ->case('false', 'md')->classes('px-2.5 py-1 text-sm')->end()
        ->end()
    ->match('themeColor', 'themeType')
        ->case('primary', 'solid')->classes('bg-primary-500 text-white')->end()
        ->case('default', 'light')->classes('bg-gray-100 text-black')->end()
        ->end()
    ->children([
        Node::make('content')->fragment()->holdsDefaultSlot(),
    ])
    ->toSchema();
