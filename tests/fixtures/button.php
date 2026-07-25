<?php

use Sprout\Component;
use Sprout\Node;

return Component::make('button', tag: 'button')
    ->slot('content')
    ->linkable('link')
    ->classes('inline-flex items-center gap-x-1 font-semibold')
    ->classes('gap-x-2')->when('arrow', true)
    ->match('pill')
    ->case('true')->classes('rounded-full')->end()
    ->case('false')->classes('rounded-lg')->end()
    ->end()
    ->match('size')
    ->case('sm')->classes('px-4 py-2 text-sm')->end()
    ->case('lg')->classes('px-8 py-4 text-lg')->end()
    ->default()->classes('px-6 py-3 text-sm')->end()
    ->end()
    ->match('themeColor', 'themeType')
    ->case('primary', 'solid')->classes('bg-primary-500 text-white')->end()
    ->case('primary', 'outline')->classes('border border-primary-500 text-primary-600')->end()
    ->default()->classes('bg-gray-900 text-white')->end()
    ->end()
    ->children([
        Node::make('content')->fragment()->holdsDefaultSlot(),
    ])
    ->toSchema();
