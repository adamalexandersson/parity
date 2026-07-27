<?php

namespace Parity\Tests\Fixtures;

use Parity\Component;
use Parity\View\Component as ParityComponent;

class PresetProbeComponent extends ParityComponent
{
    /** @return array<string, mixed> */
    public static function compose(): array
    {
        return Component::make('preset-probe', tag: 'div')
            ->preset('cols')
            ->toSchema();
    }
}
