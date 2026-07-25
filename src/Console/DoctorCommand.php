<?php

namespace Sprout\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Sprout\Config\ConfigCollector;
use Sprout\Contracts\Host;
use Sprout\Schema\SchemaValidator;
use Sprout\Schema\Version;

class DoctorCommand extends Command
{
    protected $signature = 'sprout:doctor';

    protected $description = 'Validate Sprout schemas and report issues';

    /** @var list<string> */
    protected array $reservedConfigKeys = [
        'common',
        'schemaVersion',
        'icons',
        'iconAjaxUrl',
        'iconAjaxNonce',
        'tokens',
        'classes',
        'debug',
    ];

    public function handle(ConfigCollector $collector, Host $host): int
    {
        app('sprout')->discoverComponents();
        $issues = 0;
        $validator = new SchemaValidator;

        foreach ($collector->all() as $name => $schema) {
            if (($schema['schemaVersion'] ?? null) !== Version::CURRENT) {
                $this->components->warn("[{$name}] schemaVersion mismatch.");
                $issues++;
            }

            if (empty($schema['name'])) {
                $this->components->warn("[{$name}] missing name.");
                $issues++;
            }

            if (! is_array($schema)) {
                $this->components->warn("[{$name}] schema is not an array.");
                $issues++;

                continue;
            }

            foreach ($validator->validate($schema) as $error) {
                $this->components->warn("[{$name}] {$error['path']}: {$error['message']}");
                $issues++;
            }
        }

        $issues += $this->checkManifestDrift($collector, $host);

        if ($issues === 0) {
            $this->components->info('All Sprout schemas look good.');
        } else {
            $this->components->error("Found {$issues} issue(s).");
        }

        return $issues === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function checkManifestDrift(ConfigCollector $collector, Host $host): int
    {
        $manifestRelative = config('sprout.editor.manifest_path', 'resources/js/sprout/manifest.json');
        $manifestPath = $host->path($manifestRelative);

        if (! File::exists($manifestPath)) {
            return 0;
        }

        $issues = 0;

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $manifestComponents = $manifest['components'] ?? [];

        if (! is_array($manifestComponents)) {
            $this->components->warn('[manifest] components key is invalid.');

            return 1;
        }

        $discovered = [];

        foreach ($collector->all() as $slug => $schema) {
            if (in_array($slug, $this->reservedConfigKeys, true)) {
                continue;
            }

            if (is_array($schema) && isset($schema['schemaVersion'])) {
                $discovered[$slug] = true;
            }
        }

        foreach (array_keys($discovered) as $slug) {
            if (! array_key_exists($slug, $manifestComponents)) {
                $this->components->warn("[manifest] missing discovered component \"{$slug}\". Run sprout:manifest.");
                $issues++;
            }
        }

        foreach (array_keys($manifestComponents) as $slug) {
            if (! array_key_exists($slug, $discovered)) {
                $this->components->warn("[manifest] lists unknown component \"{$slug}\". Run sprout:manifest.");
                $issues++;
            }
        }

        return $issues;
    }
}
