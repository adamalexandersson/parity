<?php

namespace Parity\Contracts;

interface Composable
{
    /** @return array<string, mixed> */
    public static function compose(): array;
}
