<?php

namespace Sprout\Registries;

use Sprout\Contracts\Host;

class TransformRegistry
{
    /** @var array<string, callable> */
    protected array $casts = [];

    public function __construct(?Host $host = null)
    {
        $this->casts = [
            'string' => fn ($value) => (string) $value,
            'boolean' => fn ($value) => (bool) $value,
            'integer' => fn ($value) => (int) $value,
            'url' => function ($value) use ($host) {
                $string = (string) $value;

                try {
                    if (function_exists('app') && app()->bound(Host::class)) {
                        return app(Host::class)->escUrl($string);
                    }
                } catch (\Throwable) {
                    //
                }

                if ($host) {
                    return $host->escUrl($string);
                }

                return $string;
            },
            'cssUrl' => function ($value) {
                $clean = preg_replace('/^url\(|\)$/i', '', (string) $value);

                return "url({$clean})";
            },
        ];
    }

    public function register(string $name, callable $callback): self
    {
        $this->casts[$name] = $callback;

        return $this;
    }

    public function cast(string $name, mixed $value): mixed
    {
        if (! isset($this->casts[$name])) {
            return $value;
        }

        return ($this->casts[$name])($value);
    }

    public function has(string $name): bool
    {
        return isset($this->casts[$name]);
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->casts);
    }
}
