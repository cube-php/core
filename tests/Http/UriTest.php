<?php

use Cube\App\App;
use Cube\App\Directory;
use Cube\Http\Uri;

function bindUriApp(string $directory = ''): void
{
    $app = (new ReflectionClass(App::class))->newInstanceWithoutConstructor();
    $caches = new ReflectionProperty(App::class, 'caches');
    $caches->setValue($app, [
        'config' => [
            'app' => [
                'directory' => $directory,
            ],
        ],
    ]);

    app()->singleton(App::class, fn() => $app);
    app()->singleton(Directory::class, fn() => new Directory(sys_get_temp_dir()));

    $instances = new ReflectionProperty(app(), 'singletonInstances');
    $values = $instances->getValue(app());
    $values[App::class] = $app;
    $instances->setValue(app(), $values);
}

it('parses a url and exposes its components', function () {
    bindUriApp();

    $uri = new Uri('https://example.test:8080/orders/list?status=paid&search=ice%20cream');

    expect($uri->getScheme())->toBe('https')
        ->and($uri->getHost())->toBe('example.test:8080')
        ->and($uri->getPath())->toBe('/orders/list')
        ->and($uri->getPort())->toBe(8080)
        ->and($uri->getQueryParams())->toBe('status=paid&search=ice%20cream')
        ->and($uri->getFullUrl())->toBe('https://example.test:8080/orders/list?status=paid&search=ice%20cream')
        ->and((string) $uri)->toBe($uri->getFullUrl());
});

it('applies the configured directory to the host and path', function () {
    bindUriApp('/cube');

    $uri = new Uri('https://example.test/cube/orders?sort=desc');

    expect($uri->getHost())->toBe('example.test/cube')
        ->and($uri->getPath())->toBe('/orders')
        ->and($uri->getUrl())->toBe('https://example.test/cube/orders')
        ->and($uri->getFullPath())->toBe('/orders?sort=desc');
});

it('reads query values and applies defaults', function () {
    bindUriApp();

    $uri = new Uri('https://example.test/search?term=<book>&page=2');

    expect($uri->getQuery('term'))->toBe('&lt;book&gt;')
        ->and($uri->getQuery('missing', 'fallback'))->toBe('fallback')
        ->and($uri->getQuery(['term']))->toBe(['&lt;book&gt;'])
        ->and($uri->getQuery('term, page, missing', ['first', 'second', 'third']))
        ->toBe(['&lt;book&gt;', '2', 'third']);
});

it('renders urls without schemes and omits usual ports', function () {
    bindUriApp();

    $uri = new Uri('http://example.test:80/orders');

    expect($uri->getUrl(false))->toBe('example.test/orders')
        ->and($uri->getUrlWithoutScheme())->toBe('example.test/orders')
        ->and($uri->getHostName())->toBe('http://example.test:80');
});

it('matches paths with and without trailing slashes', function () {
    bindUriApp();

    $uri = new Uri('https://example.test/orders/history/');

    expect($uri->matches('/orders'))->toBeTrue()
        ->and($uri->matchesExact('/orders/history'))->toBeTrue()
        ->and($uri->matchesExact('/orders/history/'))->toBeTrue()
        ->and($uri->matches('/payments'))->toBeFalse();
});

it('validates urls and rejects invalid input', function () {
    bindUriApp();

    $uri = new Uri();

    expect($uri->isUri('https://example.com'))->toBe('https://example.com')
        ->and($uri->isUri('/orders'))->toBeFalse()
        ->and(fn() => $uri->parse('/orders'))
        ->toThrow(InvalidArgumentException::class);
});

it('removes one trailing slash', function () {
    expect(Uri::removeTrailingSlash('/orders/'))->toBe('/orders')
        ->and(Uri::removeTrailingSlash('/orders'))->toBe('/orders')
        ->and(Uri::removeTrailingSlash('/'))->toBe('');
});
