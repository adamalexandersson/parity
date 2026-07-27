<?php

namespace Sprout\Tests\Fixtures;

use Sprout\Component;
use Sprout\View\Component as SproutComponent;

class ShellLinkableTestComponent extends SproutComponent
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
