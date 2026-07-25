<?php

use Sprout\Registries\TransformRegistry;
use Sprout\Render\SchemaRenderer;
use Sprout\Support\Html;

it('renders form media table and microdata structure attributes', function () {
    $compositions = require __DIR__.'/fixtures/html-compositions.php';
    $renderer = new SchemaRenderer(new TransformRegistry);

    $form = $renderer->renderStructure($compositions['form'], ['instanceId' => 'form1'], 'form-demo');
    expect($form['input']['attributes']['id'])->toBe('sprout-form1-input')
        ->and($form['label']['attributes']['for'])->toBe('sprout-form1-input')
        ->and($form['input']['attributes']['required'])->toBeTrue()
        ->and($form['select']['attributes']['multiple'])->toBeTrue()
        ->and($form['select']['children']['option']['attributes']['selected'])->toBeTrue()
        ->and($form['textarea']['attributes']['readonly'])->toBeTrue();

    $picture = $renderer->renderStructure($compositions['picture'], [], 'picture-demo');
    expect($picture['source']['attributes']['srcset'])->toBe('a.webp')
        ->and($picture['img']['attributes']['loading'])->toBe('lazy')
        ->and($picture['img']['attributes']['decoding'])->toBe('async');

    $table = $renderer->renderStructure($compositions['table'], [], 'table-demo');
    expect($table['thead']['children']['tr']['children']['th']['attributes']['scope'])->toBe('col')
        ->and($table['colgroup']['children']['col']['tag'])->toBe('col');

    $microdata = $renderer->renderStructure($compositions['microdata'], [], 'microdata-demo');
    expect($microdata['question']['attributes']['itemprop'])->toBe('name')
        ->and($microdata['answer']['attributes']['itemprop'])->toBe('acceptedAnswer');
});

it('knows the roadmap boolean attribute set', function () {
    foreach (Html::BOOLEAN_ATTRIBUTES as $attribute) {
        expect(Html::isBooleanAttribute($attribute))->toBeTrue();
    }
});
