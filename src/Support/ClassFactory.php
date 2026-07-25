<?php

namespace Sprout\Support;

use Sprout\Contracts\ClassStrategy;
use Sprout\Contracts\Host;
use Sprout\Support\ClassStrategies\PassthroughClassStrategy;
use Sprout\Support\ClassStrategies\TailwindClassStrategy;

final class ClassFactory
{
    private array $classes = [];

    private string $classString = '';

    private ClassStrategy $strategy;

    private ?Host $host;

    public function __construct(?ClassStrategy $strategy = null, ?Host $host = null)
    {
        $this->strategy = $strategy ?? self::resolveStrategy();
        $this->host = $host;
    }

    public static function resolveStrategy(?string $name = null): ClassStrategy
    {
        if ($name === null) {
            $name = 'tailwind';

            try {
                if (function_exists('config')) {
                    $name = (string) config('sprout.classes.strategy', 'tailwind');
                }
            } catch (\Throwable) {
                $name = 'tailwind';
            }
        }

        return match ($name) {
            'passthrough' => new PassthroughClassStrategy,
            default => new TailwindClassStrategy,
        };
    }

    public function get(): string
    {
        $value = $this->classString;

        try {
            if (function_exists('app') && app()->bound(Host::class)) {
                return app(Host::class)->escAttr($value);
            }
        } catch (\Throwable) {
            //
        }

        if ($this->host) {
            return $this->host->escAttr($value);
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function add(string|array $class = ''): void
    {
        if (is_array($class)) {
            foreach ($class as $c) {
                $this->addClassesFromString($c);
            }
        } else {
            $this->addClassesFromString($class);
        }

        $this->join();
    }

    public function apply(string|array $class = ''): self
    {
        $this->add($class);

        return $this;
    }

    public function remove(string $remove): bool
    {
        if (($key = array_search($remove, $this->classes, true)) !== false) {
            unset($this->classes[$key]);
            $this->classes = array_values($this->classes);
            $this->join();

            return true;
        }

        return false;
    }

    private function addClassesFromString(string $classString): void
    {
        if ($classString === '') {
            return;
        }

        foreach (array_filter(array_map('trim', explode(' ', $classString))) as $c) {
            if ($c !== '') {
                $this->classes[] = $c;
            }
        }
    }

    private function join(): void
    {
        if ($this->classes === []) {
            $this->classString = '';

            return;
        }

        $this->classString = $this->strategy->merge($this->classes);
        $this->classes = $this->classString === ''
            ? []
            : array_values(array_filter(explode(' ', $this->classString)));
    }
}
