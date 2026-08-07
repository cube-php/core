<?php

namespace Cube\Interfaces;

use Cube\Queue\Job;

interface QueueDriverInterface
{
    public function push(JobsInterface $job, int $delay = 0): void;

    public function pop(): ?Job;

    public function release(Job $job, int $delay = 0): void;

    public function delete(Job $job): void;

    public function getPendingJobsCount(): int;

    public function findJob(int $id): ?Job;
}
