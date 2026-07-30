<?php

/**
 * BEM button fixture — exercises category, block override, modifiers,
 * compounds, is/has states, and element-scoped children.
 */

use Parity\Component;
use Parity\Node;

return Component::make('bem-button', tag: 'button')
    ->block('button')
    ->category('component')
    ->modifier('pill')
    ->modifier('size')
    ->modifier('themeColor', 'theme')
    ->modifier(['themeType', 'themeColor'], 'theme')
    ->is('active')
    ->has('icon')
    ->has('arrow')
    ->children([
        Node::make('icon', tag: 'span')
            ->element('icon')
            ->visible('icon')
            ->slot('icon'),
        Node::make('label', tag: 'span')
            ->element('label')
            ->modifier('size')
            ->slot(),
        Node::make('arrow', tag: 'span')
            ->element('arrow')
            ->visible('arrow')
            ->slot('arrow'),
    ])
    ->toSchema();
