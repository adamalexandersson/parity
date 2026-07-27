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
        if (function_exists('app') && app()->runningInConsole()) {
            return true;
        }

        return function_exists('did_action') && did_action('init');
    }
}
