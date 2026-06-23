<?php

namespace Sprout\Registries;

class ComponentRegistry
{
    /** @var array<string, class-string> */
    protected array $bladeComponents = [];

    /** @var array<string, string> */
    protected array $editorComponents = [];

    public function registerBlade(string $name, string $bladeComponent): self
    {
        $this->bladeComponents[$name] = $bladeComponent;

        return $this;
    }

    public function registerEditor(string $name, string $editorKey): self
    {
        $this->editorComponents[$name] = $editorKey;

        return $this;
    }

    public function bladeComponent(string $name): ?string
    {
        return $this->bladeComponents[$name] ?? null;
    }

    public function editorComponent(string $name): ?string
    {
        return $this->editorComponents[$name] ?? null;
    }
}
