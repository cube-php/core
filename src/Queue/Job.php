<?php

namespace Cube\Queue;

final readonly class Job
{
    public function __construct(
        public int $id,
        public string $payload,
        public int $attempts
    ) {}
}
