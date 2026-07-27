<?php

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;

function parityCases(): array
{
    return require __DIR__.'/fixtures/parity-cases.php';
}

function expectedClassSnapshots(): array
{
    return [
        'button-sm-primary' => 'bg-primary-500 font-semibold gap-x-2 inline-flex items-center px-4 py-2 rounded-full text-sm text-white',
        'button-lg-default' => 'bg-gray-900 font-semibold gap-x-1 inline-flex items-center px-8 py-4 rounded-lg text-lg text-white',
        'button-md-outline' => 'border border-primary-500 font-semibold gap-x-1 inline-flex items-center px-6 py-3 rounded-lg text-primary-600 text-sm',
        'badge-md-primary' => 'bg-primary-500 font-medium inline-flex items-center justify-center px-2.5 py-1 rounded-full text-sm text-white',
        'link-md-arrow' => 'font-bold gap-x-1.5 inline-flex items-center leading-6 text-primary-600',
        'conditions-any-affordance' => 'base gap-4 has-affordance text-sm',
        'conditions-all-safe-link' => 'base gap-4 safe-link text-base',
    ];
}

function bindParityConfig(?array $caseConfig = null): void
{
    $container = Container::getInstance();

    if (! $container->bound('config')) {
        $container->instance('config', new Repository([]));
    }

    config([
        'parity.tokens' => $caseConfig['tokens'] ?? [],
        'parity.presets' => $caseConfig['presets'] ?? $caseConfig['common'] ?? [],
        'parity.classes.strategy' => $caseConfig['classes']['strategy'] ?? 'tailwind',
    ]);
}

it('produces stable normalized classes from the php renderer', function () {
    $renderer = new SchemaRenderer(new TransformRegistry);

    foreach (parityCases() as $name => $case) {
        bindParityConfig($case['config'] ?? null);

        $attributes = $renderer->renderComponentAttributes(
            $case['schema'],
            $case['props'],
            $name
        );

        $normalized = normalizeClassString($attributes['class'] ?? '');

        expect($normalized)->not->toBe('')
            ->and($normalized)->toBe(expectedClassSnapshots()[$name], "Class snapshot mismatch for case {$name}");
    }
});

it('matches php and js renderers for parity cases', function () {
    if (! file_exists(dirname(__DIR__).'/dist/parity.js')) {
        $this->markTestSkipped('dist/parity.js not built');
    }

    $script = dirname(__DIR__).'/scripts/render-parity.mjs';

    if (! file_exists($script)) {
        $this->markTestSkipped('Parity script missing');
    }

    $phpRenderer = new SchemaRenderer(new TransformRegistry);
    $cases = parityCases();
    $jsResults = runNodeParityScript($script, $cases);

    foreach ($cases as $name => $case) {
        bindParityConfig($case['config'] ?? null);

        $phpClass = normalizeClassString(
            ($phpRenderer->renderComponentAttributes($case['schema'], $case['props'], $name))['class'] ?? ''
        );

        $jsClass = normalizeClassString($jsResults[$name]['className'] ?? '');

        expect($jsClass)->toBe($phpClass, "PHP/JS parity mismatch for case {$name}");
    }
});
