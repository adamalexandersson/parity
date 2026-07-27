<?php

namespace Sprout\Tests\Fixtures;

use Sprout\Component;
use Sprout\View\Component as SproutComponent;

class ShellSlotTestComponent extends SproutComponent
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
