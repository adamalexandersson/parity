<?php

namespace Sprout\Tests\Fixtures;

use Sprout\Component;
use Sprout\Node;
use Sprout\View\Component as SproutComponent;

class ShellStructureTestComponent extends SproutComponent
{
    /** @return array<string, mixed> */
    public static function schema(): array
    {
        return Component::make('shell-structure-test', tag: 'section')
            ->classes('structure-test')
            ->slot('content')
            ->children([
                Node::make('content')->fragment()->holdsDefaultSlot(),
            ])
            ->toSchema();
    }
}
