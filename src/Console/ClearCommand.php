<?php

namespace Sprout\Console;

use Illuminate\Console\Command;
use Sprout\Config\ConfigCollector;

class ClearCommand extends Command
{
    protected $signature = 'sprout:clear';

    protected $description = 'Clear cached Sprout schemas';

    public function handle(): int
    {
        ConfigCollector::forgetCache();
        $this->components->info('Sprout schema cache cleared.');

        return self::SUCCESS;
    }
}
