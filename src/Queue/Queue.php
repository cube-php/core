<?php

namespace Cube\Queue;

use Cube\App\App;
use Cube\Interfaces\JobsInterface;
use Cube\Modules\Db\DBConnection;
use Cube\Modules\Db\DBTable;

class Queue
{
    protected string $connection;

    protected static string $schema = 'cube_jobs';

    public function __construct(protected ?string $group = null) {}

    /**
     * Push a new job onto the queue.
     * 
     * @param JobsInterface $job
     * @param int $delay
     * @param bool $checkDuplicate
     * @return void
     */
    public function push(
        JobsInterface $job,
        int $delay = 0,
        bool $check_duplicate = false
    ) {
        $payload = serialize($job);

        if ($check_duplicate && $this->hasDuplicateJob($payload)) {
            return;
        }

        static::getTable()->insert([
            'available_at' => gettime(time() + $delay),
            'payload' => $payload,
            'group_name' => $this->group,
        ]);
    }

    /**
     * Pop the next job off the queue.
     * 
     * @return Job|null
     */
    public function pop(): ?Job
    {
        $table = static::getTable();
        $connection = $table->getConnection();
        return $connection->transaction(function () use ($table) {
            $query = $table->select(['id', 'payload', 'attempts'])
                ->whereNull('reserved_at')
                ->where('available_at', '<=', gettime());

            if ($this->group) {
                $query->where('group_name', $this->group);
            }

            $query->orderByAsc('id')
                ->lock(true);

            $row = $query->fetchOne();

            if (!$row) {
                return null;
            }

            $attempts = $row->attempts + 1;

            $table->update([
                'reserved_at' => getnow(),
                'attempts' => $attempts,
            ])
                ->where('id', $row->id)
                ->fulfil();

            return new Job(
                $row->id,
                $row->payload,
                $attempts
            );
        });
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
        $entry = array(
            'reserved_at' => null,
            'available_at' => gettime(time() + $delay)
        );

        static::getTable()->update($entry)
            ->where('id', $job->id)
            ->fulfil();
    }

    /**
     * Delete a reserved job from the queue.
     * 
     * @param Job $job
     * @return void
     */
    public function delete(Job $job)
    {
        static::getTable()->delete()
            ->where('id', $job->id)
            ->fulfil();
    }

    /**
     * Get the count of pending jobs in the queue.
     * 
     * @return int
     */
    public function getPendingJobsCount(): int
    {
        $row = static::getTable()->select(['COUNT(id) AS count'])
            ->whereNull('reserved_at')
            ->where('available_at', '<=', gettime())
            ->where('group_name', $this->group)
            ->fetchOne();

        return $row ? (int) $row->count : 0;
    }

    /**
     * Get a new instance of the queue's database table.
     * 
     * @return DBTable
     */
    protected static function getTable()
    {
        return new DBTable(
            static::$schema,
            DBConnection::connection(
                App::getConfig('queue.connection')
            )
        );
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
     * @param bool $checkDuplicate
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
            $delay,
            $check_duplicate
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
        $result = static::getTable()->select(['id', 'payload', 'attempts'])
            ->where('id', $id)
            ->fetchOne();

        if (!$result) {
            return null;
        }

        return new Job(
            $result->id,
            $result->payload,
            $result->attempts
        );
    }

    /**
     * Determine if the queue already contains the same job payload.
     * 
     * @param string $payload
     * @return bool
     */
    protected function hasDuplicateJob(string $payload): bool
    {
        $query = static::getTable()->select(['id'])
            ->where('payload', $payload);

        if ($this->group === null) {
            $query->whereNull('group_name');
        } else {
            $query->where('group_name', $this->group);
        }

        return $query->fetchOne() !== null;
    }
}
