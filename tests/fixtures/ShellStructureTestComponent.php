<?php

namespace Sprout\Tests\Fixtures;

use Sprout\Component;
use Sprout\Node;
use Sprout\View\Component as SproutComponent;

class ShellStructureTestComponent extends SproutComponent
{
    /** @return array<string, mixed> */
    public static function compose(): array
    {
        return Component::make('shell-structure-test', tag: 'section')
            ->classes('structure-test')
            ->children([
                Node::make('content')->fragment()->slot(),
            ])
            ->toSchema();
    }
}
