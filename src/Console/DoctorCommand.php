<?php

namespace Parity\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Parity\Config\ConfigCollector;
use Parity\Contracts\Host;
use Parity\Editor\EditorConfigBuilder;
use Parity\Schema\SchemaValidator;
use Parity\Schema\Version;

class DoctorCommand extends Command
{
    protected $signature = 'parity:doctor';

    protected $description = 'Validate Parity schemas and report issues';

    public function handle(ConfigCollector $collector, Host $host): int
    {
        app('parity')->rediscoverComponents();
        $issues = 0;
        $validator = new SchemaValidator;

        foreach ($collector->all() as $name => $schema) {
            $schemaVersion = $schema['schemaVersion'] ?? null;

            if (is_string($schemaVersion) && $schemaVersion !== Version::CURRENT) {
                if (! Version::isCompatible($schemaVersion)) {
                    $this->components->warn("[{$name}] schemaVersion major mismatch (got {$schemaVersion}, expected ".Version::CURRENT.').');
                    $issues++;
                } else {
                    $this->components->info("[{$name}] schemaVersion minor difference (got {$schemaVersion}, expected ".Version::CURRENT.').');
                }
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
        $issues += $this->checkCacheStaleness($collector);
        $issues += $this->checkUndefinedPresets($collector);

        if ($issues === 0) {
            $this->components->info('All Parity schemas look good.');
        } else {
            $this->components->error("Found {$issues} issue(s).");
        }

        return $issues === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function checkUndefinedPresets(ConfigCollector $collector): int
    {
        $configured = config('parity.presets', []);
        $configured = is_array($configured) ? $configured : [];
        $issues = 0;

        foreach ($collector->all() as $name => $schema) {
            if (! is_array($schema)) {
                continue;
            }

            $referenced = [];
            $this->collectPresetKeys($schema, $referenced);

            foreach (array_unique($referenced) as $preset) {
                if (array_key_exists($preset, $configured)) {
                    continue;
                }

                $this->components->warn(
                    "[{$name}] references undefined preset \"{$preset}\". Publish presets with `vendor:publish --tag=parity-presets` or add the map to config('parity.presets')."
                );
                $issues++;
            }
        }

        return $issues;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<string>  $found
     */
    protected function collectPresetKeys(array $node, array &$found): void
    {
        foreach ($node['matches'] ?? [] as $match) {
            if (is_array($match) && ! empty($match['preset']) && is_string($match['preset'])) {
                $found[] = $match['preset'];
            }
        }

        foreach ($node['children'] ?? [] as $child) {
            if (is_array($child)) {
                $this->collectPresetKeys($child, $found);
            }
        }
    }

    protected function checkManifestDrift(ConfigCollector $collector, Host $host): int
    {
        $manifestRelative = config('parity.editor.manifest_path', 'resources/js/parity/manifest.json');
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
        $reserved = EditorConfigBuilder::reservedConfigKeys();

        foreach ($collector->all() as $slug => $schema) {
            if (in_array($slug, $reserved, true)) {
                continue;
            }

            if (is_array($schema) && isset($schema['schemaVersion'])) {
                $discovered[$slug] = true;
            }
        }

        foreach (array_keys($discovered) as $slug) {
            if (! array_key_exists($slug, $manifestComponents)) {
                $this->components->warn("[manifest] missing discovered component \"{$slug}\". Run parity:manifest.");
                $issues++;
            }
        }

        foreach (array_keys($manifestComponents) as $slug) {
            if (! array_key_exists($slug, $discovered)) {
                $this->components->warn("[manifest] lists unknown component \"{$slug}\". Run parity:manifest.");
                $issues++;
            }
        }

        return $issues;
    }

    protected function checkCacheStaleness(ConfigCollector $collector): int
    {
        $cached = ConfigCollector::readCache();

        if ($cached === null) {
            return 0;
        }

        $freshSchemas = $collector->all();
        $freshClasses = $collector->classes();

        ksort($freshSchemas);
        ksort($freshClasses);

        $cachedSchemas = $cached['schemas'];
        $cachedClasses = $cached['classes'];
        ksort($cachedSchemas);
        ksort($cachedClasses);

        if (
            json_encode($freshSchemas) !== json_encode($cachedSchemas)
            || json_encode($freshClasses) !== json_encode($cachedClasses)
        ) {
            $this->components->warn('[cache] parity.schemas is stale. Run parity:cache or parity:clear.');

            return 1;
        }

        return 0;
    }
}
