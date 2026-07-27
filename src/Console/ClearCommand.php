<?php

namespace Parity\Console;

use Illuminate\Console\Command;
use Parity\Config\ConfigCollector;

class ClearCommand extends Command
{
    protected $signature = 'parity:clear';

    protected $description = 'Clear cached Parity schemas';

    public function handle(): int
    {
        ConfigCollector::forgetCache();
        $this->components->info('Parity schema cache cleared.');

        return self::SUCCESS;
    }
}
