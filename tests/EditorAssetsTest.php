<?php

use Sprout\Config\ConfigCollector;
use Sprout\Editor\EditorConfigBuilder;
use Sprout\Host\LaravelHost;

it('encodes config so script tags cannot break out', function () {
    $builder = new EditorConfigBuilder(new ConfigCollector, new LaravelHost);
    $json = $builder->encode([
        'evil' => '</script><script>alert(1)</script>',
    ]);

    expect($json)->not->toContain('</script>')
        ->and($json)->toContain('\u003C')
        ->and($json)->toContain('script');
});
