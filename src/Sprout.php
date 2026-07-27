<?php

namespace Sprout;

use Illuminate\Contracts\Foundation\Application;
use Sprout\Config\ConfigCollector;
use Sprout\Registries\TransformRegistry;
use Sprout\Render\SchemaRenderer;

class Sprout
{
    public function __construct(
        protected Application $app,
    ) {}

    public function collector(): ConfigCollector
    {
        return $this->app->make(ConfigCollector::class);
    }

    public function transforms(): TransformRegistry
    {
        return $this->app->make(TransformRegistry::class);
    }

    public function renderer(): SchemaRenderer
    {
        return $this->app->make(SchemaRenderer::class);
    }

    public function discoverComponents(): void
    {
        $this->collector()->ensureDiscovered();
    }

    public function rediscoverComponents(): void
    {
        $this->collector()->rediscover(
            config('sprout.components.path'),
            config('sprout.components.namespace'),
        );
    }
}
