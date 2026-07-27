# Transforms

Transforms cast prop values before they land on attributes or styles. Built-in casts are mirrored in the editor runtime; custom casts are PHP-only today.

## Built-in casts

| Cast | Behaviour |
|------|-----------|
| `string` | `(string)` |
| `boolean` | `(bool)` |
| `integer` | `(int)` |
| `url` | Escaped via `Host::escUrl()` |
| `cssUrl` | Wrapped as `url(...)` for CSS |

```php
->attr('href')->from('link.href')->cast('url')->end()
->style('background-image')->from('image')->cast('cssUrl')->end()
```

## Register a custom transform

Use the facade from any service provider that boots with your app (an integration `Init.php`, `AppServiceProvider`, and so on):

```php
use Parity\Facades\Parity;

public function boot(): void
{
    Parity::transforms()->register('imageUrl', function ($value) {
        return is_numeric($value)
            ? wp_get_attachment_url((int) $value)
            : $value;
    });
}
```

Then:

```php
->style('background-image')
    ->from('backgroundImage')
    ->cast('imageUrl')
    ->asCssUrl()
    ->end()
```

`asCssUrl()` applies the `url(...)` wrapper after your custom cast.

## Theme pattern

Sage themes typically keep transforms in an integration, for example `app/Integrations/Parity/Transforms.php`, registered from that integration's `Init.php` during `boot()`. Auto-load the integration through your theme's integrations service provider.

## Editor parity

The Gutenberg runtime knows the five built-in casts. Custom transforms run only on the PHP side — prefer built-ins for values the editor must resolve, or accept that the canvas may show a raw prop until the front end renders.
