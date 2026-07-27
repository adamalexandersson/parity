<?php

namespace Parity\Host;

use Parity\Contracts\Host;

class LaravelHost implements Host
{
    /** @var array<string, list<callable>> */
    protected array $filters = [];

    public function name(): string
    {
        return 'laravel';
    }

    public function escAttr(string $value): string
    {
        if (function_exists('e')) {
            return (string) e($value);
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function escUrl(string $value): string
    {
        return $value;
    }

    public function filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->filters[$hook] ?? [] as $callback) {
            $value = $callback($value, ...$args);
        }

        return $value;
    }

    public function listen(string $hook, callable $callback): void
    {
        $this->filters[$hook][] = $callback;
    }

    public function path(string $relative): string
    {
        return base_path($relative);
    }

    public function url(string $relative): string
    {
        if (function_exists('asset')) {
            return asset($relative);
        }

        return '/'.ltrim($relative, '/');
    }

    public function jsonEncode(mixed $value, int $flags = 0): string
    {
        $flags |= JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;

        return (string) json_encode($value, $flags);
    }

    public function isDebug(): bool
    {
        try {
            return (bool) config('app.debug', false);
        } catch (\Throwable) {
            return false;
        }
    }

    public function shouldAutoDiscover(): bool
    {
        return true;
    }
}
