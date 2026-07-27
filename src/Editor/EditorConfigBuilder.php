<?php

namespace Sprout\Editor;

use Sprout\Config\ConfigCollector;
use Sprout\Contracts\Host;

class EditorConfigBuilder
{
    public function __construct(
        protected ConfigCollector $collector,
        protected Host $host,
    ) {}

    /**
     * Top-level keys always present in the editor config payload before
     * discovered component schemas are merged in. Used by doctor/manifest
     * so a component slug never collides with package config.
     *
     * @return list<string>
     */
    public static function reservedConfigKeys(): array
    {
        return [
            'schemaVersion',
            'presets',
            'tokens',
            'classes',
            'debug',
            'editor',
        ];
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        $config = array_merge([
            'schemaVersion' => config('sprout.schema_version', '1.0'),
            'presets' => config('sprout.presets', []),
            'tokens' => config('sprout.tokens', []),
            'classes' => [
                'strategy' => config('sprout.classes.strategy', 'tailwind'),
            ],
            'debug' => $this->host->isDebug(),
            'editor' => [
                'alpine' => config('sprout.editor.alpine', 'suppress'),
            ],
        ], $this->collector->all());

        return $this->host->filter('sprout/editor/config', $config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function encode(array $config): string
    {
        return $this->host->jsonEncode($config);
    }
}
