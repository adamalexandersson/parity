<?php

/**
 * Schema authored to match the worked example in docs/components.md.
 */

use Parity\Component;
use Parity\Node;

return Component::make('button', tag: 'button')
    ->linkable('link')
    ->classes('inline-flex items-center gap-x-1 font-semibold')
    ->match('size')
        ->case('sm')->classes('px-4 py-2 text-sm')->end()
        ->case('lg')->classes('px-8 py-4 text-lg')->end()
        ->default()->classes('px-6 py-3 text-sm')->end()
        ->end()
    ->match('themeColor', 'themeType')
        ->case('primary', 'solid')->classes('bg-primary-500 text-white')->end()
        ->default()->classes('bg-gray-900 text-white')->end()
        ->end()
    ->children([
        Node::make('icon')->fragment()->slot('icon'),
        Node::make('label')->fragment()->richText('label', 'Button text…'),
        Node::make('content')->fragment()->slot(),
    ])
    ->toSchema();
