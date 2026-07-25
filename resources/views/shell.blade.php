@php
    use Sprout\Support\Html;

    $isVoid = Html::isVoid($element ?? null);
@endphp
@if ($isVoid)
    <{{ $element }} {!! $attributes->merge($attr) !!}>
@else
    <{{ $element }} {!! $attributes->merge($attr) !!}>
        {!! $content ?? $slot !!}
    </{{ $element }}>
@endif
