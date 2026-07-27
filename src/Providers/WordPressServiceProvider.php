<?php

namespace Parity\Providers;

use Illuminate\Support\ServiceProvider;
use Parity\WordPress\EditorAssets;

class WordPressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EditorAssets::class);
    }

    public function boot(): void
    {
        $this->app->make(EditorAssets::class)->register();

        if (function_exists('add_action')) {
            add_action('init', function () {
                $this->app->make('parity')->discoverComponents();
            }, 10);
        }
    }
}
