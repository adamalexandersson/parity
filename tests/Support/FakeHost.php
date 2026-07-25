<?php

namespace Sprout\Tests\Support;

use Sprout\Contracts\Host;

class FakeHost implements Host
{
    /** @var array<string, list<callable>> */
    protected array $filters = [];

    public function __construct(
        protected string $root = '/tmp/sprout-fake-host',
        protected bool $debug = false,
        protected bool $autoDiscover = true,
        protected string $hostName = 'laravel',
    ) {}

    public function name(): string
    {
        return $this->hostName;
    }

    public function escAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function escUrl(string $value): string
    {
        return 'https://example.test/'.ltrim($value, '/');
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
        return rtrim($this->root, '/').'/'.ltrim($relative, '/');
    }

    public function url(string $relative): string
    {
        return 'https://cdn.example.test/'.ltrim($relative, '/');
    }

    public function jsonEncode(mixed $value, int $flags = 0): string
    {
        $flags |= JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;

        return (string) json_encode($value, $flags);
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function shouldAutoDiscover(): bool
    {
        return $this->autoDiscover;
    }
}
