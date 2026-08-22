<?php

use Cube\App\App;
use Cube\App\Directory;
use Cube\Exceptions\CubeCliException;
use Cube\Exceptions\RouteException;
use Cube\Http\Env;
use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Interfaces\ModelInterface;
use Cube\Misc\Collection;
use Cube\Modules\Db\DBDelete;
use Cube\Modules\Db\DBSelect;
use Cube\Modules\Db\DBTable;
use Cube\Router\Route;
use Cube\Router\RouteCollection;
use Cube\Tools\Str;

class FunctionMapClassTarget
{
    public function __construct(public string $value) {}
}

class FunctionCallProbe
{
    public function outer(): ?string
    {
        return $this->inner();
    }

    private function inner(): ?string
    {
        return get_called_class_method();
    }
}

class FunctionModelStub implements ModelInterface
{
    public function __construct(private array $payload) {}

    public static function all(?array $order = null, ?array $opts = null) {}

    public static function createEntry(array $entry) {}

    public static function delete(): DBDelete
    {
        throw new BadMethodCallException();
    }

    public static function find($primary_key) {}

    public static function findAllBy($field, $value, $order = null, $params = null) {}

    public static function findBy($field, $value) {}

    public static function findByPrimaryKey($primary_key) {}

    public static function findByPrimaryKeyAndRemove($primary_key) {}

    public static function findByPrimaryKeyAndUpdate($primary_key, array $update) {}

    public static function findOrFail($primary_key, callable $failed): ?self
    {
        throw new BadMethodCallException();
    }

    public static function findByOrFail(string $field, $value, callable $failed): ?self
    {
        throw new BadMethodCallException();
    }

    public static function fromData(string $classname, object $data) {}

    public static function getCount() {}

    public static function getCountBy($field, $value) {}

    public static function getCountQuery() {}

    public static function getFirst($field = null) {}

    public static function getLast($field = null) {}

    public static function query(): DBTable
    {
        throw new BadMethodCallException();
    }

    public static function select(...$args): DBSelect
    {
        throw new BadMethodCallException();
    }

    public static function search($field, $keyword, $limit = null, $offset = null) {}

    public static function sum(string $field) {}

    public function save(): bool
    {
        return true;
    }

    public function relation(string $model, string $field, ?string $name = null) {}

    public function relations(string $model, string $field, ?string $name = null) {}

    public function data(): array
    {
        return $this->payload;
    }

    public function remove() {}
}

function bindFunctionSingleton(string $abstract, mixed $instance): void
{
    app()->singleton($abstract, fn() => $instance);

    $instances = new ReflectionProperty(app(), 'singletonInstances');
    $values = $instances->getValue(app());
    $values[$abstract] = $instance;
    $instances->setValue(app(), $values);
}

function resetFunctionEnv(): void
{
    $reflection = new ReflectionClass(Env::class);
    $reflection->getProperty('_main_vars')->setValue(null, []);
    $reflection->getProperty('_extra_vars')->setValue(null, []);
    $reflection->getProperty('_has_loaded_main')->setValue(null, false);
    $reflection->getProperty('_has_loaded_extras')->setValue(null, false);
}

function bindFunctionApp(?string $root = null, array $config = []): string
{
    $root ??= sys_get_temp_dir() . '/cube-functions-' . uniqid();
    mkdir($root . '/app/views', 0775, true);
    mkdir($root . '/webroot/assets', 0775, true);
    file_put_contents($root . '/.env', "APP_ENV=local\nASSET_VERSION=tests\n");

    $app = (new ReflectionClass(App::class))->newInstanceWithoutConstructor();
    $caches = new ReflectionProperty(App::class, 'caches');
    $caches->setValue($app, [
        'config' => array_replace_recursive([
            'app' => [
                'directory' => '',
                'session' => [
                    'store' => 'array',
                    'lifetime' => 120,
                    'lottery' => [0, 100],
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'lax',
                ],
            ],
            'view' => [
                'cache' => false,
                'functions' => [],
                'filters' => [],
            ],
        ], $config),
    ]);

    bindFunctionSingleton(App::class, $app);
    bindFunctionSingleton(Directory::class, new Directory($root));
    resetFunctionEnv();

    return $root;
}

function makeFunctionRequest(string $uri = '/helpers', array $cookies = []): Request
{
    app()->resetScoped();

    return new Request(
        new Collection([
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => $uri,
        ]),
        new Collection(),
        new Collection($cookies),
        new Collection(),
        new Collection(),
        new Collection(),
    );
}

function bindFunctionHttp(?string $root = null): string
{
    $root = bindFunctionApp($root);
    bindFunctionSingleton(Request::class, makeFunctionRequest());
    app()->bind(Response::class, fn() => new Response());

    return $root;
}

it('covers array helper functions', function () {
    $mapped = array_map_class([
        ['name' => 'Ada'],
        ['name' => 'Linus'],
    ], 'name', FunctionMapClassTarget::class);
    $shuffled = array_shuffle([1, 2, 3]);
    sort($shuffled);

    expect(array_get_first(['a', 'b']))->toBe('a')
        ->and(array_get_first([]))->toBeNull()
        ->and(array_get_last(['a', 'b']))->toBe('b')
        ->and(array_get_last([]))->toBeNull()
        ->and($shuffled)->toBe([1, 2, 3])
        ->and(array_wrap('value'))->toBe(['value'])
        ->and(array_wrap(['value']))->toBe(['value'])
        ->and($mapped[0])->toBeInstanceOf(FunctionMapClassTarget::class)
        ->and($mapped[0]->value)->toBe('Ada')
        ->and(every(['a' => 1, 'b' => 2], fn($value, $key) => $key . $value))->toBe(['a' => 'a1', 'b' => 'b2'])
        ->and(every(new ArrayObject(['x' => 3]), fn($value, $key) => $key . $value))->toBe(['x' => 'x3'])
        ->and(array_until([1, 2, 3], fn($value) => $value > 1))->toBe(2)
        ->and(array_find_index(['first', 'second'], fn($value) => $value === 'second'))->toBe(1)
        ->and(array_find(['first', 'second'], fn($value) => str_starts_with($value, 'sec')))->toBe('second')
        ->and(array_find_all(['a' => 1, 'b' => 2, 'c' => 3], fn($value) => $value > 1))->toBe(['b' => 2, 'c' => 3])
        ->and(array_prepend_all(['one', 'two'], 'item-'))->toBe(['item-one', 'item-two']);
});

it('covers string, date, and html helper functions', function () {
    $fixedTime = strtotime('2026-08-22 10:15:30');

    expect(concat('cube', '-', 'php'))->toBe('cube-php')
        ->and(is_email('ada@example.com'))->toBe('ada@example.com')
        ->and(is_email('not-an-email'))->toBeFalse()
        ->and(str('Cube PHP'))->toBeInstanceOf(Str::class)
        ->and((string) str('Cube PHP'))->toBe('Cube PHP')
        ->and(gettime($fixedTime))->toBe('2026-08-22 10:15:30')
        ->and(getnow())->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/')
        ->and(getdays('2'))->toBe(172800)
        ->and(getmins(1.5))->toBe(90.0)
        ->and(h('SPAN', ['class' => 'tag'], 'Hello'))->toBe('<span class="tag">Hello</span>')
        ->and(h('br'))->toBe('<br />');
});

it('covers misc and filesystem helper functions', function () {
    $root = sys_get_temp_dir() . '/cube-functions-files-' . uniqid();
    mkdir($root . '/nested', 0775, true);
    file_put_contents($root . '/alpha.txt', 'alpha');
    file_put_contents($root . '/nested/beta.txt', 'beta');

    $scanned = scan_directory($root);
    $filenames = array_map(fn($file) => $file->filename, $scanned);
    sort($filenames);

    expect(generate_token(8))->toMatch('/^[a-f0-9]{16}$/')
        ->and((new FunctionCallProbe())->outer())->toBe('outer')
        ->and($filenames)->toBe(['alpha.txt', 'beta.txt'])
        ->and($scanned[1]->subdirs)->toBe(['nested'])
        ->and(model2array([
            new FunctionModelStub(['id' => 1]),
            new FunctionModelStub(['id' => 2]),
        ]))->toBe([['id' => 1], ['id' => 2]])
        ->and(unlink_dir_files($root))->toBe(2);

    rmdir($root);
});

it('covers app, env, asset, route, and response helper functions', function () {
    $root = bindFunctionHttp();
    file_put_contents($root . '/.env', "APP_ENV=production\nASSET_VERSION=release-1\nFEATURE_FLAG=enabled\n");
    resetFunctionEnv();
    resetRouteCollection();

    $route = (new Route('GET', '/users/{id}', 'Users.show'))->name('users.show');
    RouteCollection::attachRoute($route);

    $response = response();
    $redirect = redirect('/next', ['page' => 2]);
    $assetVersion = md5('release-1');

    expect(app())->toBe(app())
        ->and(env('feature_flag'))->toBe('enabled')
        ->and(url('/docs', ['q' => 'cube']))->toBe('http://example.test/docs?q=cube')
        ->and(asset('logo.png'))->toBe('http://example.test/assets/logo.png')
        ->and(asset('logo.png', true))->toBe('http://example.test/assets/logo.png?v=' . $assetVersion)
        ->and(route('users.show', ['id' => 42], ['tab' => 'profile']))->toBe('http://example.test/users/42?tab=profile')
        ->and(fn() => route('users.show'))->toThrow(RouteException::class)
        ->and(jscript('app'))->toBe('<script src="http://example.test/assets/js/app.js?v=' . $assetVersion . '"></script>')
        ->and(css('app'))->toBe('<link rel="stylesheet" href="http://example.test/assets/css/app.css?v=' . $assetVersion . '"/>')
        ->and($response)->toBeInstanceOf(Response::class)
        ->and($redirect->getHttpStatusCode())->toBe(Response::HTTP_FOUND)
        ->and($redirect->getHeaders()->get('location'))->toBe('http://example.test/next?page=2');
});

it('covers back, view, component, csrf, cli, and db helper functions', function () {
    $root = bindFunctionHttp();
    file_put_contents($root . '/app/views/greeting.twig', 'Hello {{ name }}');

    $request = app(Request::class);
    $request->session()->put('_history', ['https://previous.test/path']);

    $token = csrf_token();
    $queries = [(object) ['ran' => false], (object) ['ran' => false]];
    $result = multi_query($queries, function ($query) {
        $query->ran = true;
    });

    expect(back()->getHeaders()->get('location'))->toBe('https://previous.test/path')
        ->and(load_view('greeting', ['name' => 'Ada']))->toBe('Hello Ada')
        ->and(view('greeting', ['name' => 'Linus']))->toBe('Hello Linus')
        ->and(fn() => component('Missing'))->toThrow(InvalidArgumentException::class)
        ->and($token)->toBeString()
        ->and(csrf_form())->toBe('<input type="hidden" name="csrf_token" value="' . $token . '"/>')
        ->and(csrf($token))->toBeTrue()
        ->and(csrf('invalid-token'))->toBeFalse()
        ->and(fn() => cube('version'))->toThrow(CubeCliException::class)
        ->and(fn() => console_command('cache:clear', ['force']))->toThrow(CubeCliException::class)
        ->and($result)->toBe($queries)
        ->and($queries[0]->ran)->toBeTrue()
        ->and($queries[1]->ran)->toBeTrue();
});
