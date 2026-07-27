<?php

use Parity\Support\AttributeFactory;

it('accepts safe attribute names', function () {
    expect(AttributeFactory::isValidName('href'))->toBeTrue()
        ->and(AttributeFactory::isValidName('data-foo'))->toBeTrue()
        ->and(AttributeFactory::isValidName('aria-label'))->toBeTrue()
        ->and(AttributeFactory::isValidName('xml:lang'))->toBeTrue();
});

it('rejects unsafe attribute names', function () {
    $factory = new AttributeFactory;
    $factory->add('"><script>', 'x');
    $factory->add(' foo', 'bar');
    $factory->add('href', '/ok');

    expect($factory->toArray())->toBe(['href' => '/ok']);
});
