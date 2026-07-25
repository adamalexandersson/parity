<?php

use Sprout\Component;
use Sprout\Node;

return Component::make('void-media', tag: 'div')
    ->classes('relative')
    ->children([
        Node::make('image', tag: 'img')
            ->classes('block w-full')
            ->attr('src')->from('src')->cast('string')->end()
            ->attr('alt')->from('alt')->cast('string')->end()
            ->attr('loading', 'lazy')->end(),
        Node::make('break', tag: 'br'),
        Node::make('input', tag: 'input')
            ->attr('type', 'hidden')->end()
            ->attr('name')->from('name')->cast('string')->end()
            ->attr('disabled')->from('disabled')->cast('boolean')->end(),
    ])
    ->toSchema();
