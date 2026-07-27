<?php

use Illuminate\Support\Facades\Cache;
use Parity\Config\ConfigCollector;
use Parity\Schema\Version;

beforeEach(function () {
    ConfigCollector::forgetCache();

    config([
        'parity.components.path' => __DIR__.'/Fixtures',
        'parity.components.namespace' => 'Parity\\Tests\\Fixtures',
    ]);
});

afterEach(function () {
    ConfigCollector::forgetCache();
});

it('writes and hydrates schemas plus class map from cache', function () {
    $this->artisan('parity:cache')->assertSuccessful();

    $payload = ConfigCollector::readCache();

    expect($payload)->not->toBeNull()
        ->and($payload['schemaVersion'])->toBe(Version::CURRENT)
        ->and($payload['schemas'])->toHaveKey('shell-structure-test')
        ->and($payload['classes'])->toHaveKey('shell-structure-test');

    // Fresh collector should hydrate from cache without filesystem rediscovery markers.
    $collector = new ConfigCollector;
    app()->instance(ConfigCollector::class, $collector);

    expect($collector->get('shell-structure-test'))->toBeArray()
        ->and($collector->classFor('shell-structure-test'))->toBe(
            'Parity\\Tests\\Fixtures\\ShellStructureTestComponent'
        );
});

it('clears the schema cache', function () {
    $this->artisan('parity:cache')->assertSuccessful();

    expect(ConfigCollector::readCache())->not->toBeNull();

    $this->artisan('parity:clear')->assertSuccessful();

    expect(ConfigCollector::readCache())->toBeNull();
});

it('discards cache when schemaVersion mismatches', function () {
    Cache::forever(ConfigCollector::CACHE_KEY, [
        'schemaVersion' => '0.0',
        'schemas' => ['stale' => ['name' => 'stale', 'schemaVersion' => '0.0']],
        'classes' => [],
    ]);

    expect(ConfigCollector::readCache())->toBeNull();

    $collector = new ConfigCollector;
    $collector->ensureDiscovered();

    expect($collector->get('stale'))->toBeNull()
        ->and($collector->get('shell-structure-test'))->toBeArray();
});

it('fails doctor when the schema cache is stale', function () {
    $this->artisan('parity:cache')->assertSuccessful();

    $payload = ConfigCollector::readCache();
    $payload['schemas']['ghost-component'] = [
        'name' => 'ghost-component',
        'schemaVersion' => Version::CURRENT,
    ];
    Cache::forever(ConfigCollector::CACHE_KEY, $payload);

    // Point doctor away from any existing theme manifest.
    config([
        'parity.editor.manifest_path' => 'storage/framework/parity-tests/missing-manifest.json',
    ]);

    $this->artisan('parity:doctor')->assertFailed();
});
