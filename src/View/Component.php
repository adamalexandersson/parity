<?php

namespace Parity\View;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component as BladeComponent;
use Parity\Concerns\ComposesMarkup;
use Parity\Contracts\Composable;

/**
 * Thin convenience base for the common case.
 *
 * The real implementation lives in {@see ComposesMarkup}. Projects with their
 * own component base can `use ComposesMarkup` and `implements Composable`
 * instead of extending this class.
 */
abstract class Component extends BladeComponent implements Composable
{
    use ComposesMarkup;

    abstract public static function compose(): array;

    /**
     * Declared on the class (not only via the trait) so static analyzers treat
     * Illuminate\View\Component::render() as implemented.
     *
     * @return \Closure|Htmlable|View|string
     */
    public function render()
    {
        return $this->renderComposed();
    }

    /**
     * @return array<string, mixed>
     */
    public function data()
    {
        return $this->dataComposed();
    }
}
