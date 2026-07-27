<?php

namespace Parity\Console;

use Illuminate\Console\Command;
use Parity\Config\ConfigCollector;

class CacheCommand extends Command
{
    protected $signature = 'parity:cache';

    protected $description = 'Cache discovered Parity component schemas';

    public function handle(ConfigCollector $collector): int
    {
        app('parity')->rediscoverComponents();
        $collector->writeCache();

        $count = count($collector->all());
        $this->components->info("Parity schemas cached ({$count} components).");

        return self::SUCCESS;
    }
}
