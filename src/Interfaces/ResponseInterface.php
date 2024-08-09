<?php

namespace Cube\Interfaces;

use Cube\Misc\Collection;

interface ResponseInterface
{
    public function withAddedHeader($name, $value);

    public function withHeader($name, $value);

    public function withoutHeader($name);

    public function withStatusCode($code, $reason = '');

    public function write(...$args);

    public function json($data, ?int $status_code = null);

    public function redirect($path, array $query_params = [], $external_location = false);

    public function view($path, array $options = []);

    public function getHeaders(): Collection;

    public function getHttpStatusCode(): int;

    public function getHttpReason(): string;

    public function getProtocol(): string;

    public function getBody();

    public function getCookies(): array;
}
