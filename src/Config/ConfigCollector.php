<?php

namespace Sprout\Config;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use Sprout\Contracts\Host;
use Sprout\View\Component as SproutComponent;

class ConfigCollector
{
    /** @var array<string, array<string, mixed>> */
    protected array $configs = [];

    /** @var array<string, class-string> */
    protected array $classes = [];

    protected bool $discovered = false;

    public function register(string $name, array $schema, ?string $class = null): void
    {
        $this->configs[$name] = $schema;

        if ($class !== null) {
            $this->classes[$name] = $class;
        }
    }

    public function get(string $name): ?array
    {
        $this->ensureDiscovered();

        return $this->configs[$name] ?? null;
    }

    public function classFor(string $name): ?string
    {
        $this->ensureDiscovered();

        return $this->classes[$name] ?? null;
    }

    /** @return array<string, class-string> */
    public function classes(): array
    {
        $this->ensureDiscovered();

        return $this->classes;
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $this->ensureDiscovered();

        return $this->configs;
    }

    public function ensureDiscovered(): void
    {
        if ($this->discovered || ! $this->shouldDiscover()) {
            return;
        }

        $this->discover();
    }

    protected function shouldDiscover(): bool
    {
        if (app()->runningInConsole()) {
            return true;
        }

        try {
            if (app()->bound(Host::class)) {
                return app(Host::class)->shouldAutoDiscover();
            }
        } catch (\Throwable) {
            //
        }

        return function_exists('did_action') && did_action('init');
    }

    public function discover(?string $path = null, ?string $namespace = null): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;
        $path = $path ?? config('sprout.components.path') ?? app_path('View/Components');
        $namespace = $namespace ?? config('sprout.components.namespace') ?? 'App\\View\\Components';

        if (! is_dir($path)) {
            return;
        }

        foreach (File::allFiles($path) as $file) {
            $relative = str_replace([$path.DIRECTORY_SEPARATOR, '.php'], ['', ''], $file->getPathname());
            $class = $namespace.'\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if (! $reflection->isSubclassOf(SproutComponent::class) || $reflection->isAbstract()) {
                continue;
            }

            if (! $reflection->hasMethod('schema') || ! $reflection->getMethod('schema')->isStatic()) {
                continue;
            }

            $schema = $class::schema();

            if (! is_array($schema)) {
                continue;
            }

            $name = $schema['name'] ?? $this->nameFromClass($reflection->getShortName());
            $this->register($name, $schema, $class);
        }
    }

    private function nameFromClass(string $className): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className));
    }
}
