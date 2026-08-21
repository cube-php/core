<?php

use Cube\Http\Session\SessionHandler;
use Cube\Interfaces\RequestInterface;
use Cube\Misc\Collection;
use Cube\Router\Route;
use Cube\Router\RouteCollection;

function resetRouteCollection(): void
{
    $reflection = new ReflectionClass(RouteCollection::class);

    foreach (['all_routes', 'static_routes', 'dynamic_routes', '_name_routes'] as $property) {
        $reflection->getProperty($property)->setValue(null, []);
    }
}

function makeRoute(string|null $method, string $path, string $controller = 'Probe.index'): Route
{
    return new Route($method, $path, $controller);
}

function attachRoute(string|null $method, string $path, string $controller = 'Probe.index'): Route
{
    return RouteCollection::attachRoute(makeRoute($method, $path, $controller));
}

function makeRequest(string $method, string $path): RequestInterface
{
    return new class($method, $path) implements RequestInterface {
        public function __construct(
            private string $method,
            private string $path,
        ) {}

        public function getMethod()
        {
            return $this->method;
        }

        public function url()
        {
            return new class($this->path) {
                public function __construct(private string $path) {}

                public function getPath()
                {
                    return $this->path;
                }
            };
        }

        public function getAttribute($name) {}

        public function getBody() {}

        public function getParsedBody() {}

        public function getHeaders() {}

        public function getServer() {}

        public function getCookies(): Collection
        {
            return new Collection();
        }

        public function inputs() {}

        public function input(string|array $name, string $defaults = '') {}

        public function session(): SessionHandler
        {
            throw new RuntimeException('Session is not used by router tests.');
        }

        public function setAttribute(string $name, mixed $value) {}

        public function setCUstomMethod(string $name, callable $callback) {}
    };
}
