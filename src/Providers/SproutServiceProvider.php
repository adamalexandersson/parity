<?php

namespace Sprout\Providers;

use Illuminate\Support\ServiceProvider;
use Sprout\Config\ConfigCollector;
use Sprout\Console\CacheCommand;
use Sprout\Console\ClearCommand;
use Sprout\Console\DoctorCommand;
use Sprout\Console\GenerateEditorExportsCommand;
use Sprout\Console\MakeCommand;
use Sprout\Console\ManifestCommand;
use Sprout\Console\SafelistCommand;
use Sprout\Contracts\Host;
use Sprout\Editor\EditorConfigBuilder;
use Sprout\Registries\TransformRegistry;
use Sprout\Render\SchemaRenderer;
use Sprout\Sprout;
use Sprout\Support\HostResolver;

class SproutServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/sprout.php', 'sprout');

        $this->app->singleton(Host::class, fn () => HostResolver::resolve());

        $this->app->singleton(TransformRegistry::class, function ($app) {
            return new TransformRegistry($app->make(Host::class));
        });
        $this->app->singleton(ConfigCollector::class);
        $this->app->singleton(SchemaRenderer::class, function ($app) {
            return new SchemaRenderer(
                $app->make(TransformRegistry::class),
            );
        });
        $this->app->singleton(EditorConfigBuilder::class);

        $this->app->singleton('sprout', function ($app) {
            return new Sprout($app);
        });

        $this->app->alias('sprout', Sprout::class);

        if ($this->app->make(Host::class)->name() === 'wordpress') {
            $this->app->register(WordPressServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/sprout.php' => $this->app->configPath('sprout.php'),
            __DIR__.'/../../stubs/presets.php.stub' => config_path('sprout/presets.php'),
        ], 'sprout');

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'Sprout');

        $this->configureComponentDiscovery();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCommand::class,
                SafelistCommand::class,
                CacheCommand::class,
                ClearCommand::class,
                DoctorCommand::class,
                ManifestCommand::class,
                GenerateEditorExportsCommand::class,
            ]);
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
