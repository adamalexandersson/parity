<?php

use Parity\Host\LaravelHost;
use Parity\Host\WordPressHost;
use Parity\Support\HostResolver;
use Parity\Tests\Support\FakeHost;

it('resolves laravel host by default outside wordpress', function () {
    expect(HostResolver::resolve('laravel'))->toBeInstanceOf(LaravelHost::class)
        ->and(HostResolver::resolve('laravel')->name())->toBe('laravel')
        ->and(HostResolver::resolve('laravel')->shouldAutoDiscover())->toBeTrue();
});

it('resolves wordpress host when configured', function () {
    expect(HostResolver::resolve('wordpress'))->toBeInstanceOf(WordPressHost::class)
        ->and(HostResolver::resolve('wordpress')->name())->toBe('wordpress');
});

it('escapes attributes through the laravel host', function () {
    $host = new LaravelHost;

    expect($host->escAttr('a"b'))->toContain('&quot;');
});

it('applies laravel host filters in registration order', function () {
    $host = new LaravelHost;
    $host->listen('parity/test', fn ($value) => $value.'-one');
    $host->listen('parity/test', fn ($value) => $value.'-two');

    expect($host->filter('parity/test', 'base'))->toBe('base-one-two');
});

it('encodes json safely for script injection', function () {
    $host = new LaravelHost;
    $json = $host->jsonEncode(['x' => '</script>']);

    expect($json)->not->toContain('</script>');
});

it('supports a fake host double for adapter tests', function () {
    $host = new FakeHost('/app', debug: true, autoDiscover: false, hostName: 'laravel');

    expect($host->isDebug())->toBeTrue()
        ->and($host->shouldAutoDiscover())->toBeFalse()
        ->and($host->url('js/app.js'))->toBe('https://cdn.example.test/js/app.js');
});
