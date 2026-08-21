<?php

namespace Cube\Queue\Drivers;

use Cube\App\App;
use Cube\Interfaces\JobsInterface;
use Cube\Interfaces\QueueDriverInterface;
use Cube\Modules\Db\DBConnection;
use Cube\Modules\Db\DBConnector;
use Cube\Modules\Db\DBTable;
use Cube\Queue\Job;

class DatabaseQueueDriver implements QueueDriverInterface
{
    protected static string $schema = 'cube_jobs';

    public function __construct(protected ?string $group = null) {}

    /**
     * Push a job into the database-backed queue.
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
    ): void {
        $payload = serialize($job);

        if ($this->shouldCheckDuplicate($check_duplicate) && $this->hasDuplicate($payload)) {
            return;
        }

        static::getTable()->insert([
            'available_at' => gettime(time() + $delay),
            'payload' => $payload,
            'group_name' => $this->group,
        ]);
    }

    /**
     * Pop and reserve the next available database job.
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

            $table->update(['reserved_at' => getnow()])
                ->where('id', $row->id)
                ->fulfil();

            return new Job(
                $row->id,
                $row->payload,
                $row->attempts + 1
            );
        });
    }

    /**
     * Release a reserved database job back onto the queue.
     *
     * @param Job $job
     * @param int $delay
     * @return void
     */
    public function release(Job $job, int $delay = 0): void
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
     * Delete a database job.
     *
     * @param Job $job
     * @return void
     */
    public function delete(Job $job): void
    {
        static::getTable()->delete()
            ->where('id', $job->id)
            ->fulfil();
    }

    /**
     * Get the count of pending database jobs.
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
     * Find a database job by ID.
     *
     * @param int $id
     * @return Job|null
     */
    public function findJob(int $id): ?Job
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
     * Get the jobs table instance.
     *
     * @return DBTable
     */
    protected static function getTable(): DBTable
    {
        return new DBTable(
            static::$schema,
            DBConnection::connection(
                static::getConnectionName()
            )
        );
    }

    /**
     * Get the configured database connection name.
     *
     * @return string
     */
    protected static function getConnectionName(): string
    {
        return App::getConfig('queue.database_connection')
            ?: App::getConfig('app.queue.database_connection')
            ?: DBConnector::DEFAULT_CONNECTION_NAME;
    }

    /**
     * Determine if duplicate checks should run for this push.
     *
     * @param bool $check_duplicate
     * @return bool
     */
    protected function shouldCheckDuplicate(bool $check_duplicate): bool
    {
        return $check_duplicate || filter_var(
            App::getConfig('queue.check_duplicates'),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * Check if the same payload already exists in this queue group.
     *
     * @param string $payload
     * @return bool
     */
    protected function hasDuplicate(string $payload): bool
    {
        $query = static::getTable()->select(['id'])
            ->where('payload', $payload);

        if ($this->group) {
            $query->where('group_name', $this->group);
        } else {
            $query->whereRaw('group_name IS NULL');
        }

        return (bool) $query->fetchOne();
    }
}
