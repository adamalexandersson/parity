<?php

namespace Parity\Tests\Fixtures\Components;

use Parity\Component;
use Parity\Node;
use Parity\View\Component as ParityComponent;

class ShellOverrideTestComponent extends ParityComponent
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
