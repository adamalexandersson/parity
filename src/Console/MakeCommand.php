<?php

namespace Sprout\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCommand extends Command
{
    protected $signature = 'sprout:make {name : The component class name} {--ui : Place in the Ui namespace}';

    protected $description = 'Create a new Sprout component class';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $namespace = $this->option('ui') ? 'Ui' : 'Components';
        $class = "App\\View\\Components\\{$namespace}\\{$name}";
        $path = app_path("View/Components/{$namespace}/{$name}.php");
        $kebab = Str::kebab($name);

        if (File::exists($path)) {
            $this->components->error("Component already exists: {$path}");

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($path));

        File::put($path, $this->stub($class, $kebab, $namespace, $name));

        $this->components->info("Sprout component created: {$path}");

        return self::SUCCESS;
    }

    protected function stub(string $class, string $kebab, string $namespace, string $name): string
    {
        return <<<PHP
<?php

namespace App\\View\\Components\\{$namespace};

use Sprout\\Component;
use Sprout\\Node;
use Sprout\\View\\Component as SproutComponent;

class {$name} extends SproutComponent
{
    public function __construct(
        public string \$size = 'md',
    ) {
        parent::__construct(...func_get_args());
    }

    public static function schema(): array
    {
        return Component::make('{$kebab}')
            ->classes('inline-flex items-center')
            ->match('size')
                ->case('sm')->classes('text-sm')
                ->case('lg')->classes('text-lg')
                ->default()->classes('text-base')
                ->end()
            ->slot('content')
            ->children([
                Node::make('content')->fragment()->holdsDefaultSlot(),
            ])
            ->toSchema();
    }
}

PHP;
    }
}
