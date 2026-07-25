<?php

namespace Sprout\WordPress;

use Sprout\Contracts\Host;
use Sprout\Editor\EditorConfigBuilder;

class EditorAssets
{
    public function __construct(
        protected EditorConfigBuilder $configBuilder,
        protected Host $host,
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

        wp_add_inline_script(
            $handle,
            sprintf(
                'window.sprout=window.sprout||{};window.sprout.config=%s;',
                $this->configBuilder->encode($this->configBuilder->build())
            ),
            'before'
        );
    }

    /** @param  array<string, mixed>  $settings */
    public function injectIframeConfig(array $settings): array
    {
        $inlineScript = sprintf(
            '<script>window.sprout=window.sprout||{};window.sprout.config=%s;</script>',
            $this->configBuilder->encode($this->configBuilder->build())
        );

        if (isset($settings['__unstableResolvedAssets']['scripts'])) {
            $settings['__unstableResolvedAssets']['scripts'] .= $inlineScript;
        }

        return $settings;
    }

    protected function scriptPath(): string
    {
        $relative = config('sprout.editor.script_path', 'dist/sprout.js');
        $vendorRelative = 'vendor/adamalexandersson/sprout/'.$relative;
        $themePath = $this->host->path($vendorRelative);

        if (file_exists($themePath)) {
            return $themePath;
        }

        return dirname(__DIR__, 2).'/'.$relative;
    }

    protected function scriptUrl(): string
    {
        $relative = config('sprout.editor.script_path', 'dist/sprout.js');

        return $this->host->filter(
            'sprout/editor/script_url',
            $this->host->url($relative)
        );
    }
}
