<?php

namespace Parity;

use Illuminate\Contracts\Foundation\Application;
use Parity\Config\ConfigCollector;
use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;

class Parity
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
            config('parity.components.path'),
            config('parity.components.namespace'),
        );
    }
}
