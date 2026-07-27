<?php

use Parity\Support\ClassFactory;
use Parity\Support\ClassStrategies\PassthroughClassStrategy;
use Parity\Support\ClassStrategies\TailwindClassStrategy;

it('merges conflicting utilities with the tailwind strategy', function () {
    $factory = new ClassFactory(new TailwindClassStrategy);
    $factory->apply('p-2 p-4');

    expect($factory->get())->toBe('p-4');
});

it('deduplicates without conflict resolution for passthrough', function () {
    $factory = new ClassFactory(new PassthroughClassStrategy);
    $factory->apply('p-2');
    $factory->apply('p-4');
    $factory->apply('p-2');

    expect($factory->get())->toBe('p-2 p-4');
});
