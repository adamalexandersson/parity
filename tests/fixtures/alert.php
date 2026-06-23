<?php

use Sprout\Component;
use Sprout\Node;

return Component::make('alert')
    ->slot('wrapper.content')
    ->classes('rounded-lg border p-4')
    ->match('type')
        ->case('info')->classes('bg-blue-50 border-blue-200')->end()
        ->case('error')->classes('bg-red-50 border-red-200')->end()
        ->end()
    ->children([
        Node::make('wrapper', tag: 'div')
            ->classes('flex items-start gap-x-3')
            ->children([
                Node::make('icon', tag: 'div')
                    ->classes('leading-none')
                    ->mappedComponent('ui.icon', 'type', [
                        'info' => 'heroicon-o-information-circle',
                        'error' => 'heroicon-o-x-circle',
                    ], 'size-7'),
                Node::make('content', tag: 'div')
                    ->classes('flex-1')
                    ->holdsDefaultSlot(),
            ]),
    ])
    ->toSchema();
