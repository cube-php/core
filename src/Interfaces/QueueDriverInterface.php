<?php

namespace Cube\Interfaces;

use Cube\Queue\Job;

interface QueueDriverInterface
{
    /**
     * Push a job onto the queue.
     *
     * @param JobsInterface $job
     * @param int $delay
     * @param bool $check_duplicate
     * @return void
     */
    public function push(
        JobsInterface $job,
        int $delay = 0,
        bool $check_duplicate = false
    ): void;

    /**
     * Pop the next available job from the queue.
     *
     * @return Job|null
     */
    public function pop(): ?Job;

    /**
     * Release a reserved job back onto the queue.
     *
     * @param Job $job
     * @param int $delay
     * @return void
     */
    public function release(Job $job, int $delay = 0): void;

    /**
     * Delete a job from the queue.
     *
     * @param Job $job
     * @return void
     */
    public function delete(Job $job): void;

    /**
     * Get the number of pending jobs.
     *
     * @return int
     */
    public function getPendingJobsCount(): int;

    /**
     * Find a job by its queue ID.
     *
     * @param int $id
     * @return Job|null
     */
    public function findJob(int $id): ?Job;
}
