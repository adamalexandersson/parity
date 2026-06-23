<{{ $element }} {!! $attributes->merge($attr) !!}>
    {!! $content ?? $slot !!}
</{{ $element }}>
