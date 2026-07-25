<?php

use Sprout\Schema\SchemaValidator;

/**
 * @return array<string, array<string, bool>>
 */
function collectFixtureFeatureCoverage(): array
{
    $covered = [
        'conditionOperators' => [],
        'casts' => [],
        'outcomeTypes' => [],
        'classRuleModes' => [],
        'slotKinds' => [],
    ];

    $files = glob(__DIR__.'/fixtures/*.php') ?: [];

    foreach ($files as $file) {
        $basename = basename($file);

        // Schema fixtures return arrays. Component class fixtures must not be re-required.
        if (
            str_ends_with($file, '-cases.php')
            || str_ends_with($basename, 'Component.php')
            || str_contains($basename, 'Shell')
        ) {
            continue;
        }

        $payload = require $file;

        if (! is_array($payload)) {
            continue;
        }

        $schemas = [];

        if (isset($payload['schemaVersion'])) {
            $schemas[] = $payload;
        } elseif (isset($payload['schema'])) {
            $schemas[] = $payload['schema'];
        } else {
            foreach ($payload as $case) {
                if (! is_array($case)) {
                    continue;
                }

                if (isset($case['schemaVersion'])) {
                    $schemas[] = $case;
                } elseif (isset($case['schema'])) {
                    $schemas[] = $case['schema'];
                }
            }
        }

        foreach ($schemas as $schema) {
            walkSchemaForFeatures($schema, $covered);
        }
    }

    return $covered;
}

/**
 * @param  array<string, mixed>  $node
 * @param  array<string, array<string, bool>>  $covered
 */
function walkSchemaForFeatures(array $node, array &$covered): void
{
    foreach ($node['classRules'] ?? [] as $rule) {
        if (isset($rule['mode']) && is_string($rule['mode'])) {
            $covered['classRuleModes'][$rule['mode']] = true;
        }

        markConditionFeatures($rule['condition'] ?? null, $covered);
    }

    foreach ($node['matches'] ?? [] as $match) {
        markConditionFeatures($match['condition'] ?? null, $covered);

        foreach ($match['cases'] ?? [] as $case) {
            foreach ($case['outcomes'] ?? [] as $outcome) {
                if (isset($outcome['type'])) {
                    $covered['outcomeTypes'][$outcome['type']] = true;
                }
            }
        }

        foreach ($match['default'] ?? [] as $outcome) {
            if (isset($outcome['type'])) {
                $covered['outcomeTypes'][$outcome['type']] = true;
            }
        }
    }

    foreach ($node['attributes'] ?? [] as $attribute) {
        if (isset($attribute['cast'])) {
            $covered['casts'][$attribute['cast']] = true;
        }

        markConditionFeatures($attribute['condition'] ?? null, $covered);
    }

    foreach ($node['styles'] ?? [] as $style) {
        if (isset($style['cast'])) {
            $covered['casts'][$style['cast']] = true;
        }

        markConditionFeatures($style['condition'] ?? null, $covered);
    }

    if (isset($node['slot']) && is_array($node['slot'])) {
        if (($node['slot']['default'] ?? false) === true) {
            $covered['slotKinds']['default'] = true;
        }

        if (! empty($node['slot']['name'])) {
            $covered['slotKinds']['named'] = true;
        }
    }

    foreach ($node['children'] ?? [] as $child) {
        if (is_array($child)) {
            walkSchemaForFeatures($child, $covered);
        }
    }
}

/**
 * @param  array<string, array<string, bool>>  $covered
 */
function markConditionFeatures(mixed $condition, array &$covered): void
{
    if (! is_array($condition)) {
        return;
    }

    $operator = $condition['operator'] ?? null;

    if (is_string($operator)) {
        $covered['conditionOperators'][$operator] = true;

        if ($operator === 'equals') {
            $covered['conditionOperators']['=='] = true;
        }

        if ($operator === 'notEquals') {
            $covered['conditionOperators']['!='] = true;
        }
    }

    foreach ($condition['conditions'] ?? [] as $nested) {
        markConditionFeatures($nested, $covered);
    }
}

it('covers every schema feature from the json schema enums', function () {
    $catalog = SchemaValidator::featureCatalog();
    $covered = collectFixtureFeatureCoverage();
    $missing = [];

    foreach ($catalog as $group => $features) {
        foreach ($features as $feature) {
            if (! isset($covered[$group][$feature])) {
                $missing[] = "{$group}:{$feature}";
            }
        }
    }

    expect($missing)->toBe([], 'Missing parity fixtures for: '.implode(', ', $missing));
});
