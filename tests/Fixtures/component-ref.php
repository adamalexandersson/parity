<?php

use Parity\Component;
use Parity\Node;

return Component::make('component-ref-demo', tag: 'div')
    ->children([
        Node::make('plain', tag: 'span')
            ->component('heroicon-o-chevron-down')
            ->props(['aria-hidden' => true])
            ->end(),
        Node::make('mapped', tag: 'div')
            ->component('ui.icon')
            ->from('type')
            ->map([
                'info' => 'heroicon-o-information-circle',
                'error' => 'heroicon-o-x-circle',
            ])
            ->class('size-7')
            ->end(),
        Node::make('unmapped', tag: 'div')
            ->component('ui.icon')
            ->from('type')
            ->map([
                'info' => 'heroicon-o-information-circle',
            ])
            ->class('size-7')
            ->end(),
    ])
    ->toSchema();
