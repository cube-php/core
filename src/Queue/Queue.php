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
     * @return void
     */
    public function push(JobsInterface $job, int $delay = 0)
    {
        static::getTable()->insert([
            'available_at' => gettime(time() + $delay),
            'payload' => serialize($job),
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
                App::getConfig('app.queue.connection')
            )
        );
    }
}
