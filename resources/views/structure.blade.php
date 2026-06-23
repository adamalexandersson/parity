@php
    use Sprout\Render\SlotResolver;

    $namedSlots = $namedSlots ?? [];
    $props = $props ?? [];
@endphp

@foreach ($structure as $key => $element)
    @if ($shouldRenderElement($key, $structure))
        @php
            $slotConfig = $element['slot'] ?? null;
            $slotName = is_array($slotConfig) ? ($slotConfig['name'] ?? null) : null;
            $propSlotContent = $slotName ? ($namedSlots[$slotName] ?? ($props[$slotName] ?? null)) : null;
            $nodePath = $element['path'] ?? $key;
            $children = $element['children'] ?? [];
            $renderDefaultSlot = SlotResolver::shouldRenderDefaultSlot($element, $nodePath, $key, $slotElement);
            $isFragment = $element['fragment'] ?? false;
            $attributes = $element['attributes'] ?? [];
            $attrString = '';

            foreach ($attributes as $attrKey => $attrValue) {
                if (is_bool($attrValue)) {
                    if ($attrValue) {
                        $attrString .= ' '.$attrKey;
                    }
                } else {
                    $attrString .= ' '.$attrKey.'="'.e($attrValue).'"';
                }
            }

            $includeData = [
                'structure' => $children,
                'slotElement' => $slotElement,
                'slot' => $slot ?? null,
                'namedSlots' => $namedSlots,
                'props' => $props,
                'shouldRenderElement' => $shouldRenderElement,
            ];
        @endphp

        @if (SlotResolver::shouldSkipNamedSlotNode($element, $namedSlots))
            {{-- Named slot without content --}}
        @elseif ($slotName && $propSlotContent)
            @if (! $isFragment)
                <{{ $element['tag'] ?? 'div' }}{!! $attrString !!}>
            @endif
            {!! $propSlotContent !!}
            @if (! $isFragment)
                </{{ $element['tag'] ?? 'div' }}>
            @endif
        @else
            @if (! $isFragment)
                <{{ $element['tag'] ?? 'div' }}{!! $attrString !!}>
            @endif

            @if (! empty($element['richText']))
                {!! $slot ?? '' !!}
            @elseif ($renderDefaultSlot)
                {!! $slot ?? '' !!}
            @elseif (! empty($element['componentRef']) && ! empty($element['componentMapping']))
                @php
                    $mappingKey = $element['componentMappingKey'] ?? null;
                    $attrValue = $mappingKey ? ($props[$mappingKey] ?? null) : null;
                    $mappedValue = $attrValue !== null ? ($element['componentMapping'][$attrValue] ?? null) : null;
                @endphp

                @if ($mappedValue)
                    <x-dynamic-component :component="$element['componentRef']" :class="$element['componentClass'] ?? null">
                        @svg($mappedValue)
                    </x-dynamic-component>
                @endif
            @elseif (! empty($element['componentRef']))
                <x-dynamic-component :component="$element['componentRef']" :class="$element['componentClass'] ?? null" />
            @endif

            @if (! empty($children))
                @include('Sprout::structure', $includeData)
            @endif

            @if (! $isFragment)
                </{{ $element['tag'] ?? 'div' }}>
            @endif
        @endif
    @endif
@endforeach
