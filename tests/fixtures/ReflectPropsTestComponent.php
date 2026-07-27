<?php

namespace Sprout\Tests\Fixtures;

use Sprout\Component;
use Sprout\Node;
use Sprout\View\Component as SproutComponent;

class ReflectPropsTestComponent extends SproutComponent
{
    public function __construct(
        public string $size = 'md',
        public bool $arrow = false,
        public ?string $label = null,
    ) {}

    /** @return array<string, mixed> */
    public static function compose(): array
    {
        return Component::make('reflect-props-test', tag: 'div')
            ->classes('reflect-props-test')
            ->children([
                Node::make('content')->fragment()->slot(),
            ])
            ->toSchema();
    }
}
