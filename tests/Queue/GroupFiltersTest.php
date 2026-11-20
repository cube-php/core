<?php

use Cube\App\App;
use Cube\Commands\Jobs\JobQueueWorkerCommand;
use Cube\Modules\Db\DBConnection;
use Cube\Modules\Db\DBTable;
use Cube\Modules\Redis\RedisConnection;
use Predis\Client as PredisClient;
use Cube\Queue\Drivers\DatabaseQueueDriver;
use Cube\Queue\Drivers\RedisQueueDriver;
use Symfony\Component\Console\Input\ArgvInput;

class GroupFilterRedisDriver extends RedisQueueDriver
{
    public static RedisConnection $connection;
    public static string $testPrefix;

    /**
     * Use the isolated Redis connection supplied by the integration test.
     */
    protected static function getConnection(): RedisConnection
    {
        return static::$connection;
    }

    /**
     * Keep each test's Redis keys separate from application queues.
     */
    protected static function key(string $name): string
    {
        return static::$testPrefix . ':' . $name;
    }
}

it('normalizes whitespace and duplicate group names without discarding zero', function () {
    $command = new JobQueueWorkerCommand((new ReflectionClass(App::class))->newInstanceWithoutConstructor());
    $parse = new ReflectionMethod($command, 'parseGroups');
    expect($parse->invoke($command, [' email , sms ', 'email', '0']))->toBe(['email', 'sms', '0'])
        ->and($parse->invoke($command, []))->toBe([]);
});

it('rejects empty group names', function ($value) {
    $command = new JobQueueWorkerCommand((new ReflectionClass(App::class))->newInstanceWithoutConstructor());
    expect(fn() => (new ReflectionMethod($command, 'parseGroups'))->invoke($command, [$value]))
        ->toThrow(InvalidArgumentException::class, 'Group names must not be empty.');
})->with(['', ' ', ',email', 'email,', 'email,,sms']);

it('requires a value for each worker group flag', function ($flag) {
    $command = new JobQueueWorkerCommand((new ReflectionClass(App::class))->newInstanceWithoutConstructor());
    expect(fn() => new ArgvInput(['cube', $flag], $command->getDefinition()))
        ->toThrow(RuntimeException::class);
})->with(['--group', '-g', '--exclude-group']);

it('accepts repeated comma separated exclusions and defaults to all groups', function () {
    $command = new JobQueueWorkerCommand((new ReflectionClass(App::class))->newInstanceWithoutConstructor());
    $default = new ArgvInput(['cube'], $command->getDefinition());
    $input = new ArgvInput(['cube', '--exclude-group=email,sms', '--exclude-group=reports'], $command->getDefinition());
    expect($default->getOption('group'))->toBe([])
        ->and($default->getOption('exclude-group'))->toBe([])
        ->and((new ReflectionMethod($command, 'parseGroups'))->invoke($command, $input->getOption('exclude-group')))
        ->toBe(['email', 'sms', 'reports']);
});

it('accepts repeated and comma separated worker groups', function () {
    $app = (new ReflectionClass(App::class))->newInstanceWithoutConstructor();
    $command = new JobQueueWorkerCommand($app);
    $input = new ArgvInput(['cube', '-g', 'email,sms', '--group=0', '--exclude-group=sms'], $command->getDefinition());
    $parse = new ReflectionMethod($command, 'parseGroups');
    expect($parse->invoke($command, $input->getOption('group')))->toBe(['email', 'sms', '0'])
        ->and($parse->invoke($command, $input->getOption('exclude-group')))->toBe(['sms']);
    expect(fn() => $parse->invoke($command, ['email,']))->toThrow(InvalidArgumentException::class);
});

it('filters database groups while retaining ungrouped jobs for exclusion', function ($included, $excluded, $expected) {
    $connection = (new ReflectionClass(DBConnection::class))->newInstanceWithoutConstructor();
    $query = (new DBTable('jobs', $connection))->select(['id'])->whereNull('reserved_at');
    $driver = new DatabaseQueueDriver(groups: $included, excludedGroups: $excluded);
    (new ReflectionMethod($driver, 'applyGroupFilters'))->invoke($driver, $query);
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec('CREATE TABLE jobs (id INTEGER, group_name TEXT, reserved_at TEXT)');
    $pdo->exec("INSERT INTO jobs VALUES (1, 'email', NULL), (2, 'sms', NULL), (3, NULL, NULL), (4, '0', NULL), (5, 'email', 'reserved')");
    $statement = $pdo->prepare((string) $query);
    $statement->execute($query->getSqlParameters());
    expect($statement->fetchAll(PDO::FETCH_COLUMN))->toBe($expected);
})->with([
    [[], [], [1, 2, 3, 4]],
    [['email', 'sms'], [], [1, 2]],
    [[], ['email', 'sms'], [3, 4]],
    [['email', 'sms'], ['sms'], [1]],
    [['0'], [], [4]],
    [['missing'], [], []],
    [['email'], ['email'], []],
    [[], ['missing'], [1, 2, 3, 4]],
    [[], ['0'], [1, 2, 3]],
    [["email') OR 1=1 --"], [], []],
]);

it('atomically filters redis jobs beyond the first page without reserving skipped jobs', function () {
    $socket = getenv('QUEUE_TEST_REDIS_SOCKET');
    if (!$socket) {
        $this->markTestSkipped('Set QUEUE_TEST_REDIS_SOCKET to an isolated Redis socket.');
    }
    $redis = new PredisClient(['scheme' => 'unix', 'path' => $socket]);
    $prefix = 'group-test-' . bin2hex(random_bytes(8));
    GroupFilterRedisDriver::$connection = new RedisConnection($redis);
    GroupFilterRedisDriver::$testPrefix = $prefix;
    try {
        for ($id = 1; $id <= 103; $id++) {
            $redis->hmset("$prefix:job:$id", ['id' => $id, 'group_name' => $id <= 100 ? 'blocked' : ($id === 103 ? '' : 'email'), 'payload' => 'test', 'attempts' => 0]);
            $redis->zadd("$prefix:available", [$id => $id]);
        }
        $pop = fn($include, $exclude) => (new GroupFilterRedisDriver(groups: $include, excludedGroups: $exclude))->pop();
        $first = $pop(['email', 'blocked'], ['blocked']);
        expect($first->id)->toBe(101)
            ->and($first->attempts)->toBe(1);
        $second = $pop([], ['blocked']);
        expect($second->id)->toBe(102);
        $third = $pop([], ['blocked']);
        expect($third->id)->toBe(103)
            ->and($pop([], ['blocked']))->toBeNull()
            ->and($redis->zcard("$prefix:available"))->toBe(100)
            ->and($redis->hget("$prefix:job:1", 'attempts'))->toBe('0');
    } finally {
        $keys = $redis->keys("$prefix:*");
        if ($keys) $redis->del($keys);
    }
});
