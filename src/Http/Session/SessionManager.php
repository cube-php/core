<?php

namespace Cube\Http\Session;

use Cube\App\App;
use Cube\Http\Response;
use Cube\Http\Session\Stores\ArraySessionStore;
use Cube\Http\Session\Stores\DatabaseSessionStore;
use Cube\Http\Session\Stores\FileSessionStore;
use Cube\Interfaces\RequestInterface;
use InvalidArgumentException;

class SessionManager
{
    protected string $cookie_name = 'CUBE_SESSION_DX';

    protected int $lifetime = 7200;

    public function __construct(protected SessionStoreInterface $store)
    {
        $lottery = App::getConfig('app.session.lottery', [2, 100]);

        if (call_user_func_array('mt_rand', $lottery) <= 2) {
            $this->store->purgeExpired($this->lifetime);
        }
    }

    /**
     * Start a session for the given request
     *
     * @param RequestInterface $request
     * @return SessionHandler
     */
    public function start(RequestInterface $request): SessionHandler
    {
        $id = (string) $request->getCookies()->get($this->cookie_name);

        if (!$id) {
            return new SessionHandler(
                generate_token(30)
            );
        }

        return new SessionHandler($id, $this->store->read($id));
    }

    /**
     * Get session cookie name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->cookie_name;
    }

    /**
     * Persist session data and set cookie in response
     *
     * @param SessionHandler $session
     * @param Response $response
     * @return void
     */
    public function persist(SessionHandler $session, Response $response)
    {
        if ($session->isChanged()) {
            $this->store->write(
                $session->id(),
                $session->all(),
                $this->lifetime
            );
        }

        $secure = App::getConfig('app.session.secure', false);
        $response->withCookie(
            $this->cookie_name,
            $session->id(),
            time() + $this->lifetime,
            '/',
            false,
            $secure
        );
    }

    /**
     * Destroy session and remove cookie
     *
     * @param SessionHandler $session
     * @param Response $response
     * @return void
     */
    public function destroy(SessionHandler $session, Response $response)
    {
        $this->store->destroy($session->id());
        $secure = App::getConfig('app.session.secure', false);

        $response->withCookie(
            $this->cookie_name,
            '',
            time() - 3600,
            '/',
            false,
            $secure
        );
    }

    /**
     * Regenerate session id and persist data
     *
     * @param SessionHandler $session
     * @return void
     */
    public function regenerateId(SessionHandler $session)
    {
        $new_id = generate_token(30);
        $this->store->write($new_id, $session->all(), $this->lifetime);
        $this->store->destroy($session->id());
        $session->setId($new_id);
    }

    /**
     * Initialize session manager with configured store
     *
     * @return SessionManager
     */
    public static function init()
    {
        $store = App::getConfig('app.session.store', 'file');
        $cls = match ($store) {
            'database' => DatabaseSessionStore::class,
            'array' => ArraySessionStore::class,
            'file' => FileSessionStore::class,
            default => throw new InvalidArgumentException("Unsupported session store: $store"),
        };

        return new self(new $cls());
    }
}
