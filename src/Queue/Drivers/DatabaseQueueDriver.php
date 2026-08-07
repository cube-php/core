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

    public function push(JobsInterface $job, int $delay = 0): void
    {
        static::getTable()->insert([
            'available_at' => gettime(time() + $delay),
            'payload' => serialize($job),
            'group_name' => $this->group,
        ]);
    }

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

    public function delete(Job $job): void
    {
        static::getTable()->delete()
            ->where('id', $job->id)
            ->fulfil();
    }

    public function getPendingJobsCount(): int
    {
        $row = static::getTable()->select(['COUNT(id) AS count'])
            ->whereNull('reserved_at')
            ->where('available_at', '<=', gettime())
            ->where('group_name', $this->group)
            ->fetchOne();

        return $row ? (int) $row->count : 0;
    }

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

    protected static function getTable(): DBTable
    {
        return new DBTable(
            static::$schema,
            DBConnection::connection(static::getConnectionName())
        );
    }

    protected static function getConnectionName(): string
    {
        return App::getConfig('queue.connection')
            ?: App::getConfig('app.queue.connection')
            ?: DBConnector::DEFAULT_CONNECTION_NAME;
    }
}
