<?php

namespace Parity\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Parity\Config\ConfigCollector;
use Parity\Contracts\Host;
use Parity\Editor\EditorConfigBuilder;
use Parity\Schema\Version;
use Parity\Support\ComponentReflector;

class ManifestCommand extends Command
{
    protected $signature = 'parity:manifest {--output= : Output path relative to the host root}';

    protected $description = 'Write a committed Parity editor component manifest from discovered schemas';

    public function handle(ConfigCollector $collector, Host $host): int
    {
        app('parity')->rediscoverComponents();

        $exportNames = [];
        $reserved = EditorConfigBuilder::reservedConfigKeys();

        foreach ($collector->all() as $slug => $schema) {
            if (in_array($slug, $reserved, true)) {
                continue;
            }

            if (! is_array($schema) || ! isset($schema['schemaVersion'])) {
                continue;
            }

            $exportNames[$slug] = $this->exportNameFromSlug($slug);
        }

        ksort($exportNames);

        /** @var array<string, string> $exportNames */
        $exportNames = $host->filter('parity/editor/export-names', $exportNames);

        $components = [];

        foreach ($exportNames as $slug => $exportName) {
            $class = $collector->classFor($slug);

            $components[$slug] = [
                'exportName' => $exportName,
                'class' => $class,
                'props' => $class ? ComponentReflector::constructorProps($class) : [],
            ];
        }

        $manifest = [
            'generatedAt' => now()->toIso8601String(),
            'schemaVersion' => Version::CURRENT,
            'components' => $components,
        ];

        $output = $this->option('output')
            ?? config('parity.editor.manifest_path', 'resources/js/parity/manifest.json');

        $path = $host->path($output);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $this->components->info('Manifest written to '.$path.' ('.count($components).' components)');

        return self::SUCCESS;
    }

    public function exportNameFromSlug(string $slug): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
    }
}
