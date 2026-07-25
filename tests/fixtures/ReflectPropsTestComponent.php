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
    ) {
        parent::__construct();
    }

    /** @return array<string, mixed> */
    public static function schema(): array
    {
        return Component::make('reflect-props-test', tag: 'div')
            ->classes('reflect-props-test')
            ->slot('content')
            ->children([
                Node::make('content')->fragment()->holdsDefaultSlot(),
            ])
            ->toSchema();
    }
}
