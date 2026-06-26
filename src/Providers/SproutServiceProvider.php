<?php

namespace Sprout\Providers;

use Illuminate\Support\ServiceProvider;
use Sprout\Config\ConfigCollector;
use Sprout\Console\CacheCommand;
use Sprout\Console\ClearCommand;
use Sprout\Console\DoctorCommand;
use Sprout\Console\GenerateEditorExportsCommand;
use Sprout\Console\MakeCommand;
use Sprout\Console\SafelistCommand;
use Sprout\Editor\EditorAssets;
use Sprout\Registries\ComponentRegistry;
use Sprout\Registries\TransformRegistry;
use Sprout\Render\SchemaRenderer;
use Sprout\Sprout;

class SproutServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/sprout.php', 'sprout');

        $this->app->singleton(TransformRegistry::class);
        $this->app->singleton(ComponentRegistry::class);
        $this->app->singleton(ConfigCollector::class);
        $this->app->singleton(SchemaRenderer::class, function ($app) {
            return new SchemaRenderer(
                $app->make(TransformRegistry::class),
            );
        });
        $this->app->singleton(EditorAssets::class);

        $this->app->singleton('sprout', function ($app) {
            return new Sprout($app);
        });

        $this->app->alias('sprout', Sprout::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/sprout.php' => $this->app->configPath('sprout.php'),
            __DIR__.'/../../stubs/common.php.stub' => config_path('sprout/common.php'),
        ], 'sprout');

        $this->publishes([
            __DIR__.'/../../config/sprout.php' => $this->app->configPath('sprout.php'),
        ], 'sprout-config');

        $this->publishes([
            __DIR__.'/../../stubs/common.php.stub' => config_path('sprout/common.php'),
        ], 'sprout-common');

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'Sprout');

        $this->configureComponentDiscovery();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCommand::class,
                SafelistCommand::class,
                CacheCommand::class,
                ClearCommand::class,
                DoctorCommand::class,
                GenerateEditorExportsCommand::class,
            ]);
        }

        $this->app->make(EditorAssets::class)->register();

        if (function_exists('add_action')) {
            add_action('init', function () {
                $this->app->make('sprout')->discoverComponents();
            }, 10);
        }
    }

    protected function configureComponentDiscovery(): void
    {
        config([
            'sprout.components.path' => config('sprout.components.path') ?? app_path('View/Components'),
            'sprout.components.namespace' => config('sprout.components.namespace')
                ?? app()->getNamespace().'View\\Components',
        ]);
    }
}
