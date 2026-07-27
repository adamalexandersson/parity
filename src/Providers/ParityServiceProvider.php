<?php

namespace Parity\Providers;

use Illuminate\Support\ServiceProvider;
use Parity\Config\ConfigCollector;
use Parity\Console\CacheCommand;
use Parity\Console\ClearCommand;
use Parity\Console\DoctorCommand;
use Parity\Console\MakeCommand;
use Parity\Console\ManifestCommand;
use Parity\Console\SafelistCommand;
use Parity\Contracts\Host;
use Parity\Editor\EditorConfigBuilder;
use Parity\Parity;
use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;
use Parity\Support\HostResolver;

class ParityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/parity.php', 'parity');

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

        $this->app->singleton('parity', function ($app) {
            return new Parity($app);
        });

        $this->app->alias('parity', Parity::class);

        if ($this->app->make(Host::class)->name() === 'wordpress') {
            $this->app->register(WordPressServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/parity.php' => $this->app->configPath('parity.php'),
        ], 'parity');

        $this->publishes([
            __DIR__.'/../../stubs/presets.php.stub' => config_path('parity/presets.php'),
        ], 'parity-presets');

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'Parity');

        $this->configureComponentDiscovery();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCommand::class,
                SafelistCommand::class,
                CacheCommand::class,
                ClearCommand::class,
                DoctorCommand::class,
                ManifestCommand::class,
            ]);
        }
    }

    protected function configureComponentDiscovery(): void
    {
        config([
            'parity.components.path' => config('parity.components.path') ?? app_path('View/Components'),
            'parity.components.namespace' => config('parity.components.namespace')
                ?? app()->getNamespace().'View\\Components',
        ]);
    }
}
