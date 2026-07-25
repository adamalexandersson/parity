<?php

namespace Sprout\Support;

use Sprout\Contracts\Host;
use Sprout\Host\LaravelHost;
use Sprout\Host\WordPressHost;

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
                $value = config('sprout.host');

                return is_string($value) && $value !== '' ? $value : null;
            }
        } catch (\Throwable) {
            //
        }

        return null;
    }
}
