<?php

namespace Sprout\Contracts;

interface Composable
{
    /** @return array<string, mixed> */
    public static function compose(): array;
}
