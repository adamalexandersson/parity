<?php

namespace Sprout\Tests\Fixtures;

use Sprout\Component;
use Sprout\Node;
use Sprout\View\Component as SproutComponent;

class ShellOverrideTestComponent extends SproutComponent
{
    /** @return array<string, mixed> */
    public static function schema(): array
    {
        return Component::make('shell-override-test', tag: 'section')
            ->classes('override-test')
            ->slot('content')
            ->children([
                Node::make('content')->fragment()->holdsDefaultSlot(),
            ])
            ->toSchema();
    }
}
