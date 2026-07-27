<?php

namespace Parity\Config;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Parity\Contracts\Composable;
use Parity\Contracts\Host;
use Parity\Schema\Version;

class ConfigCollector
{
    public const CACHE_KEY = 'parity.schemas';

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

        if ($this->hydrateFromCache()) {
            return;
        }

        $this->discoverFromFilesystem();
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

    /**
     * Discover from the filesystem (used by console commands). Skips the cache
     * read path. No-ops when already discovered unless {@see rediscover()}.
     */
    public function discover(?string $path = null, ?string $namespace = null): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discoverFromFilesystem($path, $namespace);
    }

    /**
     * Clear in-memory state and rediscover from the filesystem, ignoring cache.
     */
    public function rediscover(?string $path = null, ?string $namespace = null): void
    {
        $this->configs = [];
        $this->classes = [];
        $this->discovered = false;
        $this->discoverFromFilesystem($path, $namespace);
    }

    /**
     * @return array{schemaVersion: string, schemas: array<string, array<string, mixed>>, classes: array<string, class-string>}
     */
    public function cachePayload(): array
    {
        return [
            'schemaVersion' => Version::CURRENT,
            'schemas' => $this->configs,
            'classes' => $this->classes,
        ];
    }

    public function writeCache(): void
    {
        Cache::forever(self::CACHE_KEY, $this->cachePayload());
    }

    public static function forgetCache(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable) {
            //
        }
    }

    /**
     * @return array{schemaVersion: string, schemas: array<string, array<string, mixed>>, classes: array<string, class-string>}|null
     */
    public static function readCache(): ?array
    {
        try {
            $payload = Cache::get(self::CACHE_KEY);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        if (($payload['schemaVersion'] ?? null) !== Version::CURRENT) {
            return null;
        }

        if (! isset($payload['schemas']) || ! is_array($payload['schemas'])) {
            return null;
        }

        /** @var array{schemaVersion: string, schemas: array<string, array<string, mixed>>, classes?: array<string, class-string>} $payload */
        return [
            'schemaVersion' => $payload['schemaVersion'],
            'schemas' => $payload['schemas'],
            'classes' => is_array($payload['classes'] ?? null) ? $payload['classes'] : [],
        ];
    }

    protected function hydrateFromCache(): bool
    {
        $payload = self::readCache();

        if ($payload === null) {
            return false;
        }

        $this->configs = $payload['schemas'];
        $this->classes = $payload['classes'];
        $this->discovered = true;

        return true;
    }

    protected function discoverFromFilesystem(?string $path = null, ?string $namespace = null): void
    {
        $this->discovered = true;
        $path = $path ?? config('parity.components.path') ?? app_path('View/Components');
        $namespace = $namespace ?? config('parity.components.namespace') ?? 'App\\View\\Components';

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

            if (! $reflection->implementsInterface(Composable::class) || $reflection->isAbstract()) {
                continue;
            }

            if (! $reflection->hasMethod('compose') || ! $reflection->getMethod('compose')->isStatic()) {
                continue;
            }

            $schema = $class::compose();

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
