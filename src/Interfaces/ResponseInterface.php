<?php

namespace Cube\Interfaces;

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
}