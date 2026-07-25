<?php

namespace Sprout\Contracts;

interface Host
{
    public function name(): string;

    public function escAttr(string $value): string;

    public function escUrl(string $value): string;

    public function filter(string $hook, mixed $value, mixed ...$args): mixed;

    public function path(string $relative): string;

    public function url(string $relative): string;

    public function jsonEncode(mixed $value, int $flags = 0): string;

    public function isDebug(): bool;

    public function shouldAutoDiscover(): bool;
}
