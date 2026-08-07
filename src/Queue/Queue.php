<?php

namespace Cube\Queue;

use Cube\Interfaces\JobsInterface;
use Cube\Interfaces\QueueDriverInterface;
use Cube\Queue\Drivers\QueueDriverFactory;

class Queue
{
    protected QueueDriverInterface $driver;

    public function __construct(protected ?string $group = null)
    {
        $this->driver = QueueDriverFactory::make($this->group);
    }

    /**
     * Push a new job onto the queue.
     * 
     * @param JobsInterface $job
     * @param int $delay
     * @param int $delay
     * @return void
     */
    public function push(JobsInterface $job, int $delay = 0)
    {
        $this->driver->push($job, $delay);
    }

    /**
     * Pop the next job off the queue.
     * 
     * @return Job|null
     */
    public function pop(): ?Job
    {
        return $this->driver->pop();
    }

    /**
     * Release a reserved job back onto the queue.
     * 
     * @param Job $job
     * @param int $delay
     * @return void
     */
    public function release(Job $job, int $delay = 0)
    {
        $this->driver->release($job, $delay);
    }

    /**
     * Delete a reserved job from the queue.
     * 
     * @param Job $job
     * @return void
     */
    public function delete(Job $job)
    {
        $this->driver->delete($job);
    }

    /**
     * Get the count of pending jobs in the queue.
     * 
     * @return int
     */
    public function getPendingJobsCount(): int
    {
        return $this->driver->getPendingJobsCount();
    }

    /**
     * Create a new queue instance for a specific group.
     * 
     * @param string|null $group
     * @return self
     */
    public static function forGroup(?string $group): self
    {
        return new static($group);
    }

    /**
     * Push a new job onto the queue for a specific group.
     * 
     * @param JobsInterface $job
     * @param int $delay
     * @param string|null $group
     * @param bool $check_duplicate
     * @return void
     */
    public static function pushJob(
        JobsInterface $job,
        int $delay = 0,
        ?string $group = null,
        bool $check_duplicate = false
    ) {
        static::forGroup($group)->push(
            $job,
            $delay
        );
    }

    /**
     * Find a job by its ID.
     * 
     * @param int $id
     * @return object|null
     */
    public static function findJob(int $id): ?Job
    {
        return QueueDriverFactory::make()->findJob($id);
    }
}
