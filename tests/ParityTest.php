<?php

namespace Sprout\Tests;

use PHPUnit\Framework\TestCase;
use Sprout\Render\SchemaRenderer;
use Sprout\Registries\TransformRegistry;

class ParityTest extends TestCase
{
    /** @return array<string, array{schema: array<string, mixed>, props: array<string, mixed>}> */
    protected function cases(): array
    {
        return require __DIR__.'/fixtures/parity-cases.php';
    }

    public function test_php_renderer_produces_stable_normalized_classes(): void
    {
        $renderer = new SchemaRenderer(new TransformRegistry);

        foreach ($this->cases() as $name => $case) {
            $attributes = $renderer->renderComponentAttributes(
                $case['schema'],
                $case['props'],
                $name
            );

            $normalized = $this->normalizeClassString($attributes['class'] ?? '');

            $this->assertNotSame('', $normalized, "Expected classes for case {$name}");
            $this->assertSame(
                $this->expectedSnapshots()[$name],
                $normalized,
                "Class snapshot mismatch for case {$name}"
            );
        }
    }

    public function test_js_renderer_matches_php_for_parity_cases(): void
    {
        if (! file_exists(dirname(__DIR__).'/dist/sprout.js')) {
            $this->markTestSkipped('dist/sprout.js not built');
        }

        $script = dirname(__DIR__).'/scripts/render-parity.mjs';

        if (! file_exists($script)) {
            $this->markTestSkipped('Parity script missing');
        }

        $phpRenderer = new SchemaRenderer(new TransformRegistry);
        $cases = $this->cases();
        $input = json_encode($cases, JSON_THROW_ON_ERROR);
        $command = sprintf(
            'node %s',
            escapeshellarg($script)
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__));

        if (! is_resource($process)) {
            $this->fail('Unable to start parity Node process');
        }

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $jsResults = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        foreach ($cases as $name => $case) {
            $phpClass = $this->normalizeClassString(
                ($phpRenderer->renderComponentAttributes($case['schema'], $case['props'], $name))['class'] ?? ''
            );

            $jsClass = $this->normalizeClassString($jsResults[$name]['className'] ?? '');

            $this->assertSame($phpClass, $jsClass, "PHP/JS parity mismatch for case {$name}");
        }
    }

    /** @return array<string, string> */
    protected function expectedSnapshots(): array
    {
        return [
            'button-sm-primary' => 'bg-primary-500 font-semibold gap-x-2 inline-flex items-center px-4 py-2 rounded-full text-sm text-white',
            'button-lg-default' => 'bg-gray-900 font-semibold gap-x-1 inline-flex items-center px-8 py-4 rounded-lg text-lg text-white',
            'button-md-outline' => 'border border-primary-500 font-semibold gap-x-1 inline-flex items-center px-6 py-3 rounded-lg text-primary-600 text-sm',
            'badge-md-primary' => 'bg-primary-500 font-medium inline-flex items-center justify-center px-2.5 py-1 rounded-full text-sm text-white',
            'link-md-arrow' => 'font-bold gap-x-1.5 inline-flex items-center leading-6 text-primary-600',
        ];
    }

    protected function normalizeClassString(string $classes): string
    {
        $parts = array_filter(explode(' ', $classes));

        sort($parts);

        return implode(' ', $parts);
    }
}
