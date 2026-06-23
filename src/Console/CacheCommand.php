<?php

namespace Sprout\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Sprout\Config\ConfigCollector;

class CacheCommand extends Command
{
    protected $signature = 'sprout:cache';

    protected $description = 'Cache discovered Sprout component schemas';

    public function handle(ConfigCollector $collector): int
    {
        app('sprout')->discoverComponents();
        Cache::forever('sprout.schemas', $collector->all());
        $this->components->info('Sprout schemas cached.');

        return self::SUCCESS;
    }
}
