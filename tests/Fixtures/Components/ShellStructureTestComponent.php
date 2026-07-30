<?php

namespace Parity\Tests\Fixtures\Components;

use Parity\Component;
use Parity\Node;
use Parity\View\Component as ParityComponent;

class ShellStructureTestComponent extends ParityComponent
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
