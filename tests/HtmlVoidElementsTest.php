<?php

use Sprout\Support\Html;

it('lists the html void elements', function () {
    expect(Html::VOID_ELEMENTS)->toBe([
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
    ]);
});

it('detects void tags case-insensitively', function () {
    expect(Html::isVoid('img'))->toBeTrue()
        ->and(Html::isVoid('IMG'))->toBeTrue()
        ->and(Html::isVoid('div'))->toBeFalse()
        ->and(Html::isVoid(null))->toBeFalse();
});

it('matches the javascript void element list', function () {
    $jsPath = dirname(__DIR__).'/resources/js/support/voidElements.js';
    $source = file_get_contents($jsPath);

    preg_match('/export const VOID_ELEMENTS = \[([\s\S]*?)\];/', $source, $matches);

    expect($matches)->toHaveKey(1);

    preg_match_all("/'([^']+)'/", $matches[1], $items);

    expect($items[1])->toBe(Html::VOID_ELEMENTS);
});
