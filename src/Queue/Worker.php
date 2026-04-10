<?php

namespace Cube\Queue;

use Cube\App\App;
use Throwable;

class Worker
{
    public function __construct(
        protected Queue $queue,
        protected int $sleep = 1,
        protected int $delay = 0,
        protected bool $managed = false,
    ) {}

    /**
     * Start processing jobs from the queue.
     * 
     * @return void
     */
    public function work()
    {
        $max_attempts = App::getConfig('queue.max_attempts', 3);
        $max_cycle = App::getConfig('queue.max_idle_cycle', 0);
        $max_jobs = App::getConfig('queue.max_jobs', 0);

        $processed_jobs = 0;
        $idle_cycle_count = 0;

        while (true) {
            $job = $this->queue->pop();

            if ($this->managed) {
                $idle_cycle_count++;

                if ($max_cycle > 0 && $idle_cycle_count >= $max_cycle) {
                    echo "Worker reached max cycle limit of {$max_cycle}. Exiting...\n";
                    break;
                }
            }

            if ($max_jobs > 0 && $processed_jobs >= $max_jobs) {
                break;
            }

            if (!$job) {
                sleep($this->sleep);
                continue;
            }

            $idle_cycle_count = 0;
            $processed_jobs++;

            echo "Processing job with id: {$job->id}\n";

            try {
                $payload = unserialize($job->payload);
                $payload->handle();
                $this->queue->delete($job);
            } catch (Throwable $e) {
                echo "Error processing job with id: {$job->id}. Error: {$e->getMessage()}\n";
                if ($job->attempts >= $max_attempts) {
                    $this->queue->delete($job);
                    continue;
                }

                $this->queue->release($job, $this->delay);
            }
        }
    }
}
