<?php

namespace Parity\Support;

use Parity\Contracts\Host;
use Parity\Host\LaravelHost;
use Parity\Host\WordPressHost;

final class HostResolver
{
    public static function resolve(?string $configured = null): Host
    {
        $configured ??= self::configuredName();

        if ($configured === 'wordpress' || ($configured === null && function_exists('add_action'))) {
            return new WordPressHost;
        }

        return new LaravelHost;
    }

    protected static function configuredName(): ?string
    {
        try {
            if (function_exists('config')) {
                $value = config('parity.host');

                return is_string($value) && $value !== '' ? $value : null;
            }
        } catch (\Throwable) {
            //
        }

        return null;
    }
}
