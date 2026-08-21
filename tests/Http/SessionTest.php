<?php

use Cube\App\App;
use Cube\Http\Cookie\CookieItem;
use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Http\Session\SessionHandler;
use Cube\Http\Session\SessionManager;
use Cube\Http\Session\SessionStoreInterface;
use Cube\Misc\Collection;

class SessionSpyStore implements SessionStoreInterface
{
    public array $sessions = [];

    public array $writes = [];

    public array $destroyed = [];

    public array $purged = [];

    /**
     * Read stored test session data.
     *
     * @param string $id Session id
     * @return array
     */
    public function read(string $id): array
    {
        return $this->sessions[$id] ?? [];
    }

    /**
     * Record and store session writes for assertions.
     *
     * @param string $id Session id
     * @param array $data Session data
     * @param int $lifetime Session lifetime in seconds
     * @return void
     */
    public function write(string $id, array $data, int $lifetime = 7200): void
    {
        $this->writes[] = compact('id', 'data', 'lifetime');
        $this->sessions[$id] = $data;
    }

    /**
     * Record destroyed session ids for assertions.
     *
     * @param string $id Session id
     * @return void
     */
    public function destroy(string $id): void
    {
        $this->destroyed[] = $id;
        unset($this->sessions[$id]);
    }

    /**
     * Record purge calls for assertions.
     *
     * @param int $lifetime Session lifetime in seconds
     * @return void
     */
    public function purgeExpired(int $lifetime)
    {
        $this->purged[] = $lifetime;
    }
}

/**
 * Bind a minimal app config needed by SessionManager during tests.
 *
 * @param array $session Session config overrides
 * @return void
 */
function bindSessionConfig(array $session = []): void
{
    $app = (new ReflectionClass(App::class))->newInstanceWithoutConstructor();
    $caches = new ReflectionProperty(App::class, 'caches');
    $caches->setValue($app, [
        'config' => [
            'app' => [
                'session' => array_merge([
                    'lifetime' => 120,
                    'lottery' => [100, 100],
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => CookieItem::SAMESITE_STRICT,
                ], $session),
            ],
        ],
    ]);

    app()->singleton(App::class, fn() => $app);
    $instances = new ReflectionProperty(app(), 'singletonInstances');
    $values = $instances->getValue(app());
    $values[App::class] = $app;
    $instances->setValue(app(), $values);
}

/**
 * Create an isolated request carrying optional session cookies.
 *
 * @param array $cookies Request cookies
 * @return Request
 */
function makeSessionRequest(array $cookies = []): Request
{
    app()->resetScoped();

    return new Request(
        new Collection([
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/session',
        ]),
        new Collection(),
        new Collection($cookies),
        new Collection(),
        new Collection(),
        new Collection(),
    );
}

/**
 * Create an isolated response with a fresh scoped cookie jar.
 *
 * @return Response
 */
function makeSessionResponse(): Response
{
    app()->resetScoped();
    return new Response();
}

it('starts a new session when no session cookie exists', function () {
    bindSessionConfig();
    $store = new SessionSpyStore();
    $manager = new SessionManager($store);

    $session = $manager->start(makeSessionRequest());

    expect($session->id())->toHaveLength(60)
        ->and($session->all())->toBe([]);
});

it('loads an existing session from the configured session cookie', function () {
    bindSessionConfig();
    $store = new SessionSpyStore();
    $sessionId = str_repeat('a', 60);
    $store->sessions[$sessionId] = ['user_id' => 42];
    $manager = new SessionManager($store);

    $session = $manager->start(makeSessionRequest([
        $manager->getName() => $sessionId,
    ]));

    expect($session->id())->toBe($sessionId)
        ->and($session->get('user_id'))->toBe(42);
});

it('ignores malformed session cookie ids instead of reading them from storage', function () {
    bindSessionConfig();
    $store = new SessionSpyStore();
    $store->sessions['../../outside'] = ['user_id' => 42];
    $manager = new SessionManager($store);

    $session = $manager->start(makeSessionRequest([
        $manager->getName() => '../../outside',
    ]));

    expect($session->id())->not->toBe('../../outside')
        ->and($session->id())->toHaveLength(60)
        ->and($session->all())->toBe([]);
});

it('does not resume syntactically valid session cookie ids missing from storage', function () {
    bindSessionConfig();
    $store = new SessionSpyStore();
    $manager = new SessionManager($store);
    $unknownSessionId = str_repeat('b', 60);

    $session = $manager->start(makeSessionRequest([
        $manager->getName() => $unknownSessionId,
    ]));

    expect($session->id())->not->toBe($unknownSessionId)
        ->and($session->id())->toHaveLength(60)
        ->and($session->all())->toBe([]);
});

it('persists changed sessions and queues a secure httponly session cookie', function () {
    bindSessionConfig();
    $store = new SessionSpyStore();
    $manager = new SessionManager($store);
    $session = new SessionHandler('session-id');
    $session->put('user_id', 42);
    $response = makeSessionResponse();

    $manager->persist($session, $response);
    $cookie = $response->getCookies()[0];

    expect($store->writes)->toHaveCount(1)
        ->and($store->writes[0]['id'])->toBe('session-id')
        ->and($store->writes[0]['data'])->toBe(['user_id' => 42])
        ->and($store->writes[0]['lifetime'])->toBe(120)
        ->and($cookie->name)->toBe($manager->getName())
        ->and($cookie->value)->toBe('session-id')
        ->and($cookie->path)->toBe('/')
        ->and($cookie->domain)->toBe('')
        ->and($cookie->secure)->toBeTrue()
        ->and($cookie->httponly)->toBeTrue()
        ->and($cookie->samesite)->toBe(CookieItem::SAMESITE_STRICT);
});

it('destroys sessions and expires the session cookie with secure attributes intact', function () {
    bindSessionConfig(['samesite' => CookieItem::SAMESITE_NONE, 'secure' => false]);
    $store = new SessionSpyStore();
    $store->sessions['session-id'] = ['user_id' => 42];
    $manager = new SessionManager($store);
    $session = new SessionHandler('session-id');
    $response = makeSessionResponse();

    $manager->destroy($session, $response);
    $cookie = $response->getCookies()[0];

    expect($store->destroyed)->toBe(['session-id'])
        ->and($cookie->name)->toBe($manager->getName())
        ->and($cookie->value)->toBe('')
        ->and($cookie->expires)->toBeLessThan(time())
        ->and($cookie->secure)->toBeTrue()
        ->and($cookie->httponly)->toBeTrue()
        ->and($cookie->samesite)->toBe(CookieItem::SAMESITE_NONE);
});

it('regenerates session ids and removes the old session record', function () {
    bindSessionConfig();
    $store = new SessionSpyStore();
    $store->sessions['old-session'] = ['role' => 'admin'];
    $manager = new SessionManager($store);
    $session = new SessionHandler('old-session', ['role' => 'admin']);

    $manager->regenerateId($session);

    expect($session->id())->not->toBe('old-session')
        ->and($session->id())->toHaveLength(60)
        ->and($store->sessions[$session->id()])->toBe(['role' => 'admin'])
        ->and($store->destroyed)->toBe(['old-session']);
});
