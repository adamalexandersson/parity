<?php

namespace Sprout\Contracts;

interface ClassStrategy
{
    /**
     * @param  list<string>  $classes
     */
    public function merge(array $classes): string;
}
