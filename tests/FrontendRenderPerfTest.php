<?php

use Parity\Component;
use Parity\Host\WordPressHost;
use Parity\Node;
use Parity\Render\SchemaRenderer;
use Parity\Tests\Fixtures\ShellStructureTestComponent;

it('reuses compose() schemas across component instances', function () {
    $first = new ShellStructureTestComponent;
    $first->data();

    $second = new ShellStructureTestComponent;
    $second->data();

    $schemaA = (new ReflectionClass($first))->getProperty('schema')->getValue($first);
    $schemaB = (new ReflectionClass($second))->getProperty('schema')->getValue($second);

    expect($schemaA)->toBe($schemaB);
});

it('builds attributes and structure together without diverging from separate passes', function () {
    $schema = Component::make('combined', tag: 'div')
        ->classes('root')
        ->children([
            Node::make('inner', tag: 'span')->classes('child'),
        ])
        ->toSchema();

    $renderer = app(SchemaRenderer::class);
    $combined = $renderer->renderComponent($schema, ['instanceId' => 'a'], 'combined');
    $attrs = $renderer->renderComponentAttributes($schema, ['instanceId' => 'a'], 'combined');
    $structure = $renderer->renderStructure($schema, ['instanceId' => 'a'], 'combined');

    expect($combined['attributes']['class'] ?? '')->toBe($attrs['class'] ?? '')
        ->and($combined['structure'])->toBe($structure);
});

it('wordpress host does not auto-discover on the public frontend', function () {
    $host = new class extends WordPressHost
    {
        protected function runningInConsole(): bool
        {
            return false;
        }
    };

    expect($host->shouldAutoDiscover())->toBeFalse();
});

it('wordpress host still auto-discovers for console commands', function () {
    $host = new WordPressHost;

    expect($host->shouldAutoDiscover())->toBeTrue();
});
