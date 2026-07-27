<?php

use Parity\Support\ComponentReflector;
use Parity\Tests\Fixtures\ReflectPropsTestComponent;

it('reflects constructor props from a parity component class', function () {
    $props = ComponentReflector::constructorProps(ReflectPropsTestComponent::class);
    $byName = collect($props)->keyBy('name');

    expect($byName)->toHaveKeys(['size', 'arrow', 'label'])
        ->and($byName['size']['type'])->toBe('string')
        ->and($byName['size']['default'])->toBe('md')
        ->and($byName['size']['required'])->toBeFalse()
        ->and($byName['arrow']['type'])->toBe('bool')
        ->and($byName['arrow']['default'])->toBeFalse()
        ->and($byName['label']['type'])->toBe('string')
        ->and($byName['label']['required'])->toBeFalse();
});

it('returns an empty list for unknown classes', function () {
    expect(ComponentReflector::constructorProps('App\\Does\\Not\\Exist'))->toBe([]);
});
