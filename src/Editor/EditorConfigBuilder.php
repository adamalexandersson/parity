<?php

namespace Parity\Editor;

use Parity\Config\ConfigCollector;
use Parity\Contracts\Host;

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
            'schemaVersion' => config('parity.schema_version', '1.0'),
            'presets' => config('parity.presets', []),
            'tokens' => config('parity.tokens', []),
            'classes' => [
                'strategy' => config('parity.classes.strategy', 'tailwind'),
            ],
            'debug' => $this->host->isDebug(),
            'editor' => [
                'alpine' => config('parity.editor.alpine', 'suppress'),
            ],
        ], $this->collector->all());

        return $this->host->filter('parity/editor/config', $config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function encode(array $config): string
    {
        return $this->host->jsonEncode($config);
    }
}
