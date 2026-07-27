<?php

namespace Parity\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCommand extends Command
{
    protected $signature = 'parity:make {name : The component class name} {--ui : Place in the Ui namespace}';

    protected $description = 'Create a new Parity component class';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $ui = (bool) $this->option('ui');
        $namespace = $ui ? 'App\\View\\Components\\Ui' : 'App\\View\\Components';
        $relativeDir = $ui ? 'View/Components/Ui' : 'View/Components';
        $path = app_path("{$relativeDir}/{$name}.php");
        $kebab = Str::kebab($name);

        if (File::exists($path)) {
            $this->components->error("Component already exists: {$path}");

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($path));

        File::put($path, $this->stub($namespace, $kebab, $name));

        $this->components->info("Parity component created: {$path}");

        return self::SUCCESS;
    }

    protected function stub(string $namespace, string $kebab, string $name): string
    {
        return <<<PHP
<?php

namespace {$namespace};

use Parity\\Component;
use Parity\\Node;
use Parity\\View\\Component as ParityComponent;

class {$name} extends ParityComponent
{
    public function __construct(
        public string \$size = 'md',
    ) {}

    public static function compose(): array
    {
        return Component::make('{$kebab}')
            ->classes('inline-flex items-center')
            ->match('size')
                ->case('sm')->classes('text-sm')->end()
                ->case('lg')->classes('text-lg')->end()
                ->default()->classes('text-base')->end()
                ->end()
            ->children([
                Node::make('content')->fragment()->slot(),
            ])
            ->toSchema();
    }
}

PHP;
    }
}
