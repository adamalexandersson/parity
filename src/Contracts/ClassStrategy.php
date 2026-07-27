<?php

namespace Parity\Contracts;

interface ClassStrategy
{
    /**
     * @param  list<string>  $classes
     */
    public function merge(array $classes): string;
}
