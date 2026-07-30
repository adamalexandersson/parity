<?php

namespace Parity\Tests\Fixtures\Components;

use Parity\Component;
use Parity\View\Component as ParityComponent;

class ShellSlotTestComponent extends ParityComponent
{
    public ?string $element = 'h2';

    /** @return array<string, mixed> */
    public static function compose(): array
    {
        return Component::make('shell-slot-test', tag: 'div')
            ->classes('slot-test')
            ->toSchema();
    }
}
