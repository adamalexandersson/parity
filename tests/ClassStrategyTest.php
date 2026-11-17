<?php

use Parity\Support\ClassFactory;
use Parity\Support\ClassStrategies\PassthroughClassStrategy;
use Parity\Support\ClassStrategies\TailwindClassStrategy;
use TailwindMerge\Support\Config;

it('merges conflicting utilities with the tailwind strategy', function () {
    $factory = new ClassFactory(new TailwindClassStrategy);
    $factory->apply('p-2 p-4');

    expect($factory->get())->toBe('p-4');
});

it('keeps semantic font sizes beside text colors from host config', function () {
    TailwindClassStrategy::flush();

    Config::setAdditionalConfig([
        'classGroups' => [
            'font-size' => [['text' => ['body', 'preamble']]],
        ],
    ]);

    try {
        $factory = new ClassFactory(new TailwindClassStrategy);
        $factory->apply('px-7 text-body text-button-primary-solid-text');

        expect($factory->get())->toBe('px-7 text-body text-button-primary-solid-text');
    } finally {
        Config::setAdditionalConfig([]);
        TailwindClassStrategy::flush();
    }
});

it('deduplicates without conflict resolution for passthrough', function () {
    $factory = new ClassFactory(new PassthroughClassStrategy);
    $factory->apply('p-2');
    $factory->apply('p-4');
    $factory->apply('p-2');

    expect($factory->get())->toBe('p-2 p-4');
});
