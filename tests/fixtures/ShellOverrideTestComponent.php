<?php

namespace Sprout\Tests\Fixtures;

use Sprout\Component;
use Sprout\Node;
use Sprout\View\Component as SproutComponent;

class ShellOverrideTestComponent extends SproutComponent
{
    /** @return array<string, mixed> */
    public static function compose(): array
    {
        return Component::make('shell-override-test', tag: 'section')
            ->classes('override-test')
            ->children([
                Node::make('content')->fragment()->slot(),
            ])
            ->toSchema();
    }
}
