<?php

use Parity\Contracts\Host;
use Parity\Host\LaravelHost;
use Parity\Providers\WordPressServiceProvider;

it('binds the laravel host outside wordpress', function () {
    expect(app(Host::class))->toBeInstanceOf(LaravelHost::class)
        ->and(app(Host::class)->name())->toBe('laravel');
});

it('does not load the wordpress provider when the host is laravel', function () {
    expect(app()->providerIsLoaded(WordPressServiceProvider::class))->toBeFalse();
});
