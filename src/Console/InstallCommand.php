<?php

namespace Sprout\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'sprout:install
                            {--force : Overwrite existing published files}';

    protected $description = 'Publish Sprout theme integration files for Sage/Acorn';

    public function handle(): int
    {
        $published = false;

        if ($this->publishProvider()) {
            $published = true;
        }

        if ($this->publishCommonConfig()) {
            $published = true;
        }

        if (! $published) {
            $this->components->warn('Nothing to publish. Files already exist. Use --force to overwrite.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->info('Sprout theme integration published.');
        $this->line('Register the provider in <comment>app/Providers/ThemeServiceProvider.php</comment>:');
        $this->line('  <comment>$this->app->register(SproutServiceProvider::class);</comment>');
        $this->newLine();
        $this->line('Extend transforms and editor config in <comment>app/Providers/SproutServiceProvider.php</comment> as needed.');

        return self::SUCCESS;
    }

    protected function publishProvider(): bool
    {
        $destination = app_path('Providers/SproutServiceProvider.php');

        if (File::exists($destination) && ! $this->option('force')) {
            return false;
        }

        File::ensureDirectoryExists(dirname($destination));
        File::copy($this->stubPath('SproutServiceProvider.stub'), $destination);

        $this->components->info('Published SproutServiceProvider.php');

        return true;
    }

    protected function publishCommonConfig(): bool
    {
        $destination = config_path('sprout/common.php');

        if (File::exists($destination) && ! $this->option('force')) {
            return false;
        }

        File::ensureDirectoryExists(dirname($destination));
        File::copy($this->stubPath('common.php.stub'), $destination);

        $this->components->info('Published config/sprout/common.php');

        return true;
    }

    protected function stubPath(string $filename): string
    {
        return dirname(__DIR__, 2).'/stubs/'.$filename;
    }
}
