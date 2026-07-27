<?php

namespace Parity\Tests\Fixtures;

use Parity\Component;
use Parity\Node;
use Parity\View\Component as ParityComponent;

class ReflectPropsTestComponent extends ParityComponent
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
