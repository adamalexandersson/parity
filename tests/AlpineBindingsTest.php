<?php

use Parity\Component;
use Parity\Node;
use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;

it('serializes alpine helpers and interpolates id placeholders', function () {
    $schema = Component::make('alpine-demo', tag: 'div')
        ->uniqueId('root')
        ->xData('accordion({ single: true })')
        ->xInit("init('{root}')")
        ->children([
            Node::make('trigger', tag: 'button')
                ->uniqueId('trigger')
                ->attr('aria-controls')->idRef('panel')->end()
                ->xOn('click', "toggle('{panel}')")
                ->xBind('aria-expanded', "isOpen('{panel}')"),
            Node::make('panel', tag: 'div')
                ->uniqueId('panel')
                ->xShow("isOpen('{panel}')")
                ->xCloak(),
        ])
        ->toSchema();

    $renderer = new SchemaRenderer(new TransformRegistry);
    $props = ['instanceId' => 'demo'];

    $root = $renderer->renderComponentAttributes($schema, $props, 'alpine-demo');
    $structure = $renderer->renderStructure($schema, $props, 'alpine-demo');

    expect($root['id'])->toBe('parity-demo-root')
        ->and($root['x-data'])->toBe('accordion({ single: true })')
        ->and($root['x-init'])->toBe("init('parity-demo-root')")
        ->and($structure['trigger']['attributes']['aria-controls'])->toBe('parity-demo-panel')
        ->and($structure['trigger']['attributes']['x-on:click'])->toBe("toggle('parity-demo-panel')")
        ->and($structure['panel']['attributes']['x-show'])->toBe("isOpen('parity-demo-panel')")
        ->and($structure['panel']['attributes']['x-cloak'])->toBeTrue();
});

it('resolves placeholders from the alpine accordion fixture', function () {
    $schema = require __DIR__.'/Fixtures/Schemas/alpine-accordion.php';
    $renderer = new SchemaRenderer(new TransformRegistry);
    $structure = $renderer->renderStructure($schema, ['instanceId' => 'fix'], 'alpine-accordion');

    expect($structure['trigger']['attributes']['x-on:click'])->toBe("toggle('parity-fix-panel')")
        ->and($structure['panel']['attributes']['id'])->toBe('parity-fix-panel');
});
