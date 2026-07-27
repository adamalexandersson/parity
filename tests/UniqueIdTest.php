<?php

use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;
use Parity\Support\InstanceIds;

it('generates deterministic unique ids and id refs', function () {
    $schema = require __DIR__.'/Fixtures/unique-id-a11y.php';
    $renderer = new SchemaRenderer(new TransformRegistry);
    $props = ['instanceId' => 'demo'];

    $structure = $renderer->renderStructure($schema, $props, 'unique-id-a11y');

    expect($structure['field']['attributes']['id'])->toBe('parity-demo-field')
        ->and($structure['label']['attributes']['for'])->toBe('parity-demo-field')
        ->and($structure['field']['attributes']['aria-describedby'])->toBe('parity-demo-hint')
        ->and($structure['hint']['attributes']['id'])->toBe('parity-demo-hint');
});

it('matches php and js instance key fingerprints', function () {
    $props = ['count' => 2, 'size' => 'md'];
    $php = InstanceIds::resolveInstanceKey('card', $props);

    $module = dirname(__DIR__).'/resources/js/support/instanceIds.js';
    $command = 'node --input-type=module -e '.escapeshellarg(
        'import { resolveInstanceKey } from '.json_encode($module).';'
        .' console.log(resolveInstanceKey("card", '.json_encode($props).'));'
    );

    $js = trim((string) shell_exec($command.' 2>/dev/null'));

    expect($js)->toBe($php);
});
