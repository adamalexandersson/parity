<?php

namespace Parity\Host;

use Parity\Contracts\Host;

class WordPressHost implements Host
{
    public function name(): string
    {
        return 'wordpress';
    }

    public function escAttr(string $value): string
    {
        if (function_exists('esc_attr')) {
            return esc_attr($value);
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function escUrl(string $value): string
    {
        if (function_exists('esc_url')) {
            return esc_url($value);
        }

        return $value;
    }

    public function filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (function_exists('apply_filters')) {
            return apply_filters($hook, $value, ...$args);
        }

        return $value;
    }

    public function path(string $relative): string
    {
        if (function_exists('get_theme_file_path')) {
            return get_theme_file_path($relative);
        }

        return base_path($relative);
    }

    public function url(string $relative): string
    {
        $vendorRelative = 'vendor/adamalexandersson/parity/'.$relative;

        if (function_exists('get_theme_file_uri') && function_exists('get_theme_file_path')) {
            if (file_exists(get_theme_file_path($vendorRelative))) {
                return get_theme_file_uri($vendorRelative);
            }
        }

        if (function_exists('plugins_url')) {
            return plugins_url($relative, dirname(__DIR__, 2).'/composer.json');
        }

        return '/'.ltrim($relative, '/');
    }

    public function jsonEncode(mixed $value, int $flags = 0): string
    {
        $flags |= JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;

        if (function_exists('wp_json_encode')) {
            return (string) wp_json_encode($value, $flags);
        }

        return (string) json_encode($value, $flags);
    }

    public function isDebug(): bool
    {
        return defined('SCRIPT_DEBUG') && SCRIPT_DEBUG;
    }

    public function shouldAutoDiscover(): bool
    {
        if ($this->runningInConsole()) {
            return true;
        }

        // Editor / admin need the full schema catalog for window.parity.config.
        // Public frontend pages compose schemas per Blade instance and should not
        // pay for filesystem discovery (or even cache hydrate) on every request.
        return function_exists('is_admin') && is_admin();
    }

    protected function runningInConsole(): bool
    {
        try {
            return function_exists('app')
                && method_exists(app(), 'runningInConsole')
                && app()->runningInConsole();
        } catch (\Throwable) {
            return false;
        }
    }
}
