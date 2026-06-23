<?php

namespace Sprout\Support;

use TailwindMerge\TailwindMerge;

final class ClassFactory
{
    private array $classes = [];

    private string $classString = '';

    public function get(): string
    {
        $value = $this->classString;

        if (function_exists('esc_attr')) {
            return esc_attr($value);
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

        $this->classString = TailwindMerge::instance()->merge(...$this->classes);
        $this->classes = array_values(array_filter(explode(' ', $this->classString)));
    }
}
