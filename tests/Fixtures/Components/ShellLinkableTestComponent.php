<?php

namespace Parity\Tests\Fixtures\Components;

use Parity\Component;
use Parity\View\Component as ParityComponent;

class ShellLinkableTestComponent extends ParityComponent
{
    public $element = null;

    /** @var array<string, string> */
    public $link = [
        'href' => 'https://example.com',
    ];

    /** @return array<string, mixed> */
    public static function compose(): array
    {
        return Component::make('shell-linkable-test', tag: 'button')
            ->linkable('link')
            ->classes('linkable-test')
            ->toSchema();
    }
}
