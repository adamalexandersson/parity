<?php

namespace Sprout\Support;

final class Html
{
    /** @var list<string> */
    public const VOID_ELEMENTS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    /** @var list<string> */
    public const BOOLEAN_ATTRIBUTES = [
        'disabled',
        'checked',
        'required',
        'readonly',
        'multiple',
        'selected',
        'open',
        'hidden',
        'controls',
        'autoplay',
        'muted',
        'loop',
        'playsinline',
        'defer',
        'async',
        'novalidate',
        'reversed',
        'itemscope',
    ];

    public static function isVoid(?string $tag): bool
    {
        if ($tag === null || $tag === '') {
            return false;
        }

        return in_array(strtolower($tag), self::VOID_ELEMENTS, true);
    }

    public static function isBooleanAttribute(string $name): bool
    {
        return in_array(strtolower($name), self::BOOLEAN_ATTRIBUTES, true);
    }
}
