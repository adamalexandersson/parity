<?php

namespace Sprout\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearCommand extends Command
{
    protected $signature = 'sprout:clear';

    protected $description = 'Clear cached Sprout schemas';

    public function handle(): int
    {
        Cache::forget('sprout.schemas');
        $this->components->info('Sprout schema cache cleared.');

        return self::SUCCESS;
    }
}
