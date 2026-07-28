<?php

use Parity\Render\SchemaRenderer;
use Parity\Support\ClassStrategies\TailwindClassStrategy;
use Parity\Tests\Fixtures\ShellStructureTestComponent;
use Parity\Tests\TestCase;

pest()->extend(TestCase::class);

function benchMs(int $iterations, callable $fn): float
{
    $fn();

    $start = hrtime(true);

    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }

    return (hrtime(true) - $start) / 1e6;
}

it('measures frontend hot-path speedups', function () {
    $iterations = 1000;
    $schema = require __DIR__.'/Fixtures/button.php';
    $idSchema = require __DIR__.'/Fixtures/unique-id-a11y.php';

    $props = [
        'pill' => false,
        'size' => 'md',
        'themeColor' => 'primary',
        'themeType' => 'solid',
        'arrow' => false,
        'instanceId' => 'bench-1',
        'class' => 'extra-class',
    ];

    $composeUncached = benchMs($iterations, fn () => ShellStructureTestComponent::compose());

    $composeCached = benchMs($iterations, function () {
        static $cached = null;
        $cached ??= ShellStructureTestComponent::compose();

        return $cached;
    });

    $parts = [
        'inline-flex items-center justify-center gap-2 font-medium rounded transition',
        'px-6 py-3 text-sm',
        'bg-primary-600 text-white hover:bg-primary-700',
        'extra-class',
    ];

    // Cold: rebuild the merger + reparse every call (pre-optimization behaviour).
    $mergeCold = benchMs(200, function () use ($parts) {
        TailwindClassStrategy::flush();

        (new TailwindClassStrategy)->merge($parts);
    }) / 200;

    // Warm: shared merger instance + memoized result.
    $strategy = new TailwindClassStrategy;
    $mergeWarm = benchMs($iterations, fn () => $strategy->merge($parts)) / $iterations;

    $renderer = app(SchemaRenderer::class);

    // ID-heavy schemas pay for predeclareIds twice in the old dual-pass API.
    $dual = benchMs($iterations, function () use ($renderer, $idSchema) {
        $renderer->renderComponentAttributes($idSchema, ['instanceId' => 'a'], 'unique-id-a11y');
        $renderer->renderStructure($idSchema, ['instanceId' => 'a'], 'unique-id-a11y');
    });

    $single = benchMs($iterations, function () use ($renderer, $idSchema) {
        $renderer->renderComponent($idSchema, ['instanceId' => 'a'], 'unique-id-a11y');
    });

    $buttonRender = benchMs($iterations, function () use ($renderer, $schema, $props) {
        $renderer->renderComponent($schema, $props, 'button');
    });

    $componentBoot = benchMs($iterations, function () {
        (new ShellStructureTestComponent)->data();
    });

    $composeSpeedup = $composeUncached / max($composeCached, 0.0001);
    $mergeSpeedup = $mergeCold / max($mergeWarm, 0.000001);
    $passSpeedup = $dual / max($single, 0.0001);

    dump([
        'compose_uncached_per_op_ms' => round($composeUncached / $iterations, 4),
        'compose_cached_per_op_ms' => round($composeCached / $iterations, 4),
        'compose_speedup' => round($composeSpeedup, 1).'x',
        'merge_cold_per_op_ms' => round($mergeCold, 4),
        'merge_warm_per_op_ms' => round($mergeWarm, 4),
        'merge_speedup' => round($mergeSpeedup, 1).'x',
        'single_pass_speedup' => round($passSpeedup, 2).'x',
        'button_render_per_op_ms' => round($buttonRender / $iterations, 4),
        'component_boot_per_op_ms' => round($componentBoot / $iterations, 4),
        'iterations' => $iterations,
    ]);

    expect($composeSpeedup)->toBeGreaterThan(10)
        ->and($mergeSpeedup)->toBeGreaterThan(10)
        ->and($passSpeedup)->toBeGreaterThan(1.0)
        // A single component render must stay well under 0.1ms.
        ->and($buttonRender / $iterations)->toBeLessThan(0.1);
});
