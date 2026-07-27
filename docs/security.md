# Security notes

## Attribute names and values

`Sprout\Support\AttributeFactory` rejects attribute names outside `^[a-zA-Z_:][\w:.-]*$`. Values are escaped with `e()` / `htmlspecialchars` when emitted into Blade attribute strings in `resources/views/structure.blade.php`.

Boolean attributes are emitted as bare names only when truthy.

## Inline editor config

`Sprout\Editor\EditorConfigBuilder::encode()` delegates to `Host::jsonEncode()`. The WordPress host encodes with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` so values containing `</script>` cannot break out of the inline `<script>` injection used for `window.sprout.config` (see `Sprout\WordPress\EditorAssets`).

## Unescaped Blade (`{!! !!}`)

| Location | Expression | Why it is safe |
|----------|------------|----------------|
| `structure.blade.php` | `{!! $attrString !!}` | Built from validated attribute names and `e()`-escaped values |
| `structure.blade.php` | `{!! $propSlotContent !!}` / `{!! $slot !!}` | Trusted HTML from Blade component slots / named slot props (same trust model as Laravel `<x-*>` slots) |
| `shell.blade.php` | `{!! $attributes->merge($attr) !!}` | Laravel `ComponentAttributeBag` escapes attribute values |
| `shell.blade.php` | `{!! $content ?? $slot !!}` | Trusted Blade slot / pre-rendered structure HTML from the package shell |

Theme authors must not pass untrusted user HTML into schema slots without sanitizing it first.
