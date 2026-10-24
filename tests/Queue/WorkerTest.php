<?php

use Cube\App\App;
use Cube\Exceptions\RedisConnectionException;
use Cube\Queue\Job;
use Cube\Queue\Queue;
use Cube\Queue\Worker;

class WorkerConnectionFailureQueue extends Queue
{
    public int $popCalls = 0;

    public function __construct() {}

    public function pop(): ?Job
    {
        $this->popCalls++;
        throw new RedisConnectionException('Error while reading line from the server');
    }
}

function bindWorkerConfig(): void
{
    $app = (new ReflectionClass(App::class))->newInstanceWithoutConstructor();
    $caches = new ReflectionProperty(App::class, 'caches');
    $caches->setValue($app, [
        'config' => [
            'queue' => [
                'max_attempts' => 3,
                'max_idle_cycle' => 2,
                'max_jobs' => 0,
            ],
        ],
    ]);

    app()->singleton(App::class, fn() => $app);
    $instances = new ReflectionProperty(app(), 'singletonInstances');
    $values = $instances->getValue(app());
    $values[App::class] = $app;
    $instances->setValue(app(), $values);
}

it('keeps workers alive after transient redis connection failures', function () {
    bindWorkerConfig();
    $queue = new WorkerConnectionFailureQueue();
    $worker = new Worker($queue, sleep: 0, managed: true);

    ob_start();
    $worker->work();
    $output = ob_get_clean();

    expect($queue->popCalls)->toBe(1)
        ->and($output)->toContain('Queue connection lost:')
        ->and($output)->toContain('Retrying in 0 seconds...');
});
