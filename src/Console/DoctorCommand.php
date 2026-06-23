<?php

namespace Sprout\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Sprout\Config\ConfigCollector;
use Sprout\Schema\Version;

class DoctorCommand extends Command
{
    protected $signature = 'sprout:doctor';

    protected $description = 'Validate Sprout schemas and report issues';

    public function handle(ConfigCollector $collector): int
    {
        app('sprout')->discoverComponents();
        $issues = 0;

        foreach ($collector->all() as $name => $schema) {
            if (($schema['schemaVersion'] ?? null) !== Version::CURRENT) {
                $this->components->warn("[{$name}] schemaVersion mismatch.");
                $issues++;
            }

            if (empty($schema['name'])) {
                $this->components->warn("[{$name}] missing name.");
                $issues++;
            }
        }

        if ($issues === 0) {
            $this->components->info('All Sprout schemas look good.');
        }

        return $issues === 0 ? self::SUCCESS : self::FAILURE;
    }
}
