<?php

namespace Sprout\Editor;

use Sprout\Config\ConfigCollector;

class EditorAssets
{
    public function __construct(
        protected ConfigCollector $collector,
    ) {}

    public function register(): void
    {
        if (! function_exists('add_action')) {
            return;
        }

        add_action('enqueue_block_editor_assets', [$this, 'enqueue'], 20);
        add_filter('block_editor_settings_all', [$this, 'injectIframeConfig'], 20);
    }

    public function enqueue(): void
    {
        $handle = config('sprout.editor.script_handle', 'sprout');
        $scriptPath = $this->scriptPath();

        if (! file_exists($scriptPath)) {
            return;
        }

        wp_register_script(
            $handle,
            $this->scriptUrl(),
            ['wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            filemtime($scriptPath),
            true
        );

        wp_enqueue_script($handle);

        wp_localize_script(
            $handle,
            config('sprout.editor.config_global', 'componentConfig'),
            $this->buildConfig()
        );
    }

    /** @param array<string, mixed> $settings */
    public function injectIframeConfig(array $settings): array
    {
        $configJson = wp_json_encode($this->buildConfig());
        $inlineScript = sprintf(
            '<script>window.%s=%s;</script>',
            config('sprout.editor.config_global', 'componentConfig'),
            $configJson
        );

        if (isset($settings['__unstableResolvedAssets']['scripts'])) {
            $settings['__unstableResolvedAssets']['scripts'] .= $inlineScript;
        }

        return $settings;
    }

    /** @return array<string, mixed> */
    public function buildConfig(): array
    {
        $config = array_merge([
            'schemaVersion' => config('sprout.schema_version', '1.0'),
            'common' => config('sprout.common', []),
        ], $this->collector->all());

        return apply_filters('sprout/editor/config', $config);
    }

    protected function scriptPath(): string
    {
        $relative = config('sprout.editor.script_path', 'dist/sprout.js');

        if ($themePath = $this->themeVendorPath($relative)) {
            return $themePath;
        }

        return dirname(__DIR__, 2).'/'.$relative;
    }

    protected function scriptUrl(): string
    {
        return apply_filters(
            'sprout/editor/script_url',
            $this->defaultScriptUrl()
        );
    }

    protected function defaultScriptUrl(): string
    {
        $relative = config('sprout.editor.script_path', 'dist/sprout.js');

        if (function_exists('get_theme_file_uri')) {
            $vendorRelative = 'vendor/adamalexandersson/sprout/'.$relative;

            if (file_exists(get_theme_file_path($vendorRelative))) {
                return get_theme_file_uri($vendorRelative);
            }
        }

        return plugins_url($relative, dirname(__DIR__, 2).'/composer.json');
    }

    protected function themeVendorPath(string $relative): ?string
    {
        if (! function_exists('get_theme_file_path')) {
            return null;
        }

        $path = get_theme_file_path('vendor/adamalexandersson/sprout/'.$relative);

        return file_exists($path) ? $path : null;
    }
}
