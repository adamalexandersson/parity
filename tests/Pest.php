<?php

use Parity\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Bind Orchestra Testbench only for tests that need an application container.
| Unit tests use PHPUnit\Framework\TestCase by default.
|
*/

pest()->extend(TestCase::class)->in(
    'ShellFallbackTest.php',
    'VendorPublishTest.php',
    'ProviderHostTest.php',
    'ManifestAndDoctorTest.php',
    'SchemaExceptionTest.php',
    'SchemaCacheTest.php',
);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function normalizeClassString(string $classes): string
{
    $parts = array_filter(explode(' ', $classes));

    sort($parts);

    return implode(' ', $parts);
}

/**
 * @param  array<string, mixed>  $structure
 * @return array<string, mixed>
 */
function normalizeStructure(array $structure, bool $preserveAttributes = false): array
{
    ksort($structure);

    $normalized = [];

    foreach ($structure as $key => $node) {
        $attributes = $node['attributes'] ?? [];
        $children = $node['children'] ?? [];

        if ($preserveAttributes) {
            $normalizedAttributes = $attributes;
            unset($normalizedAttributes['className']);

            if (isset($attributes['className']) && ! isset($normalizedAttributes['class'])) {
                $normalizedAttributes['class'] = $attributes['className'];
            }

            ksort($normalizedAttributes);
        } else {
            $normalizedAttributes = [
                'class' => $attributes['class'] ?? $attributes['className'] ?? '',
            ];
        }

        $normalized[$key] = [
            'path' => $node['path'] ?? null,
            'tag' => $node['tag'] ?? null,
            'fragment' => (bool) ($node['fragment'] ?? false),
            'slot' => $node['slot'] ?? null,
            'component' => $node['component'] ?? null,
            'attributes' => $normalizedAttributes,
            'children' => normalizeStructure(is_array($children) ? $children : [], $preserveAttributes),
        ];
    }

    return $normalized;
}

/**
 * @param  array<string, mixed>  $cases
 * @return array<string, mixed>
 */
function runNodeParityScript(string $script, array $cases): array
{
    $input = json_encode($cases, JSON_THROW_ON_ERROR);
    $command = sprintf('node %s', escapeshellarg($script));

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__));

    if (! is_resource($process)) {
        throw new RuntimeException('Unable to start Node parity process');
    }

    fwrite($pipes[0], $input);
    fclose($pipes[0]);

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
}
