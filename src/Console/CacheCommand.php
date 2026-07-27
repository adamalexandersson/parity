<?php

namespace Sprout\Console;

use Illuminate\Console\Command;
use Sprout\Config\ConfigCollector;

class CacheCommand extends Command
{
    protected $signature = 'sprout:cache';

    protected $description = 'Cache discovered Sprout component schemas';

    public function handle(ConfigCollector $collector): int
    {
        app('sprout')->rediscoverComponents();
        $collector->writeCache();

        $count = count($collector->all());
        $this->components->info("Sprout schemas cached ({$count} components).");

        return self::SUCCESS;
    }
}
