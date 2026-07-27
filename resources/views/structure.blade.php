@php
    use Sprout\Render\SlotResolver;
    use Sprout\Support\Html;

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
            $tag = $element['tag'] ?? 'div';
            $isVoid = ! $isFragment && Html::isVoid($tag);
            $attributes = $element['attributes'] ?? [];
            $component = $element['component'] ?? null;
            $attrString = '';

            foreach ($attributes as $attrKey => $attrValue) {
                if (is_bool($attrValue) || Html::isBooleanAttribute((string) $attrKey)) {
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

            $bladeIconsAvailable = class_exists(\BladeUI\Icons\Factory::class);
        @endphp

        @if (SlotResolver::shouldSkipNamedSlotNode($element, $namedSlots))
            {{-- Named slot without content --}}
        @elseif ($slotName && $propSlotContent)
            @if ($isVoid)
                <{{ $tag }}{!! $attrString !!}>
            @elseif (! $isFragment)
                <{{ $tag }}{!! $attrString !!}>
                {!! $propSlotContent !!}
                </{{ $tag }}>
            @else
                {!! $propSlotContent !!}
            @endif
        @elseif ($isVoid)
            <{{ $tag }}{!! $attrString !!}>
        @else
            @if (! $isFragment)
                <{{ $tag }}{!! $attrString !!}>
            @endif

            @if (! empty($element['richText']))
                @php
                    $rtProp = $element['richText']['prop'] ?? null;
                    $hasRichTextProp = is_string($rtProp) && array_key_exists($rtProp, $props);
                @endphp
                @if ($hasRichTextProp)
                    {!! $props[$rtProp] ?? '' !!}
                @else
                    {!! $slot ?? '' !!}
                @endif
            @elseif ($renderDefaultSlot)
                {!! $slot ?? '' !!}
            @elseif (! empty($component['ref']) && ! empty($component['map']))
                @php
                    $mappingKey = $component['from'] ?? null;
                    $attrValue = $mappingKey ? ($props[$mappingKey] ?? null) : null;
                    $mappedValue = $attrValue !== null ? ($component['map'][$attrValue] ?? null) : null;
                @endphp

                @if ($mappedValue)
                    @php
                        $dynAttrs = new \Illuminate\View\ComponentAttributeBag(array_filter(
                            array_merge(
                                ['class' => $component['class'] ?? null],
                                $component['props'] ?? [],
                            ),
                            static fn ($value) => $value !== null && $value !== false,
                        ));
                    @endphp
                    <x-dynamic-component :component="$component['ref']" :attributes="$dynAttrs">
                        @if ($bladeIconsAvailable)
                            @svg($mappedValue)
                        @elseif (! app()->isProduction())
                            @php
                                throw new \RuntimeException(
                                    'Mapped component icon "'.$mappedValue.'" requires blade-ui-kit/blade-icons. '
                                    .'Run `composer require blade-ui-kit/blade-icons` or remove the mapped SVG path.'
                                );
                            @endphp
                        @endif
                    </x-dynamic-component>
                @endif
            @elseif (! empty($component['ref']))
                @php
                    $dynAttrs = new \Illuminate\View\ComponentAttributeBag(array_filter(
                        array_merge(
                            ['class' => $component['class'] ?? null],
                            $component['props'] ?? [],
                        ),
                        static fn ($value) => $value !== null && $value !== false,
                    ));
                @endphp
                <x-dynamic-component :component="$component['ref']" :attributes="$dynAttrs" />
            @endif

            @if (! empty($children))
                @include('Sprout::structure', $includeData)
            @endif

            @if (! $isFragment)
                </{{ $tag }}>
            @endif
        @endif
    @endif
@endforeach
