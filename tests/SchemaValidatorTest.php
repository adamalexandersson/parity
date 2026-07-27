<?php

use Parity\Schema\SchemaValidator;

it('accepts a valid component schema', function () {
    $validator = new SchemaValidator;
    $schema = require __DIR__.'/Fixtures/button.php';

    expect($validator->validate($schema))->toBe([]);
});

it('rejects a schema missing required fields', function () {
    $validator = new SchemaValidator;

    $issues = $validator->validate([
        'schemaVersion' => '1.0',
    ]);

    expect($issues)->not->toBeEmpty();
});

it('exposes feature enums for parity coverage', function () {
    $catalog = SchemaValidator::featureCatalog();

    expect($catalog['conditionOperators'])->toContain('any')
        ->and($catalog['conditionOperators'])->toContain('all')
        ->and($catalog['classRuleModes'])->toContain('element')
        ->and($catalog['classRuleModes'])->toContain('modifier')
        ->and($catalog['outcomeTypes'])->toContain('classes');
});
