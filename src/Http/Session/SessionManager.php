<?php

namespace Cube\Http\Session;

use Cube\App\App;
use Cube\Http\Response;
use Cube\Interfaces\RequestInterface;

class SessionManager
{
    protected string $cookie_name = 'CUBE_SESSION_DX';

    protected int $lifetime = 7200;

    public function __construct(protected SessionStoreInterface $store)
    {
        $this->lifetime = (int) (
            App::getConfig('app.session.lifetime')
                ?: App::getConfig('app.session_lifetime')
                ?: $this->lifetime
        );

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

        if (!$this->isValidSessionId($id)) {
            return new SessionHandler(
                generate_token(30)
            );
        }

        $data = $this->store->read($id);

        if (!$data) {
            return new SessionHandler(
                generate_token(30)
            );
        }

        return new SessionHandler($id, $data);
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

        $secure = $this->getCookieSecure();
        $httponly = $this->getCookieHttpOnly();
        $samesite = $this->getCookieSameSite();

        $response->withCookie(
            $this->cookie_name,
            $session->id(),
            time() + $this->lifetime,
            '/',
            '',
            $secure,
            $httponly,
            $samesite
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
        $secure = $this->getCookieSecure();
        $httponly = $this->getCookieHttpOnly();
        $samesite = $this->getCookieSameSite();

        $response->withCookie(
            $this->cookie_name,
            '',
            time() - 3600,
            '/',
            '',
            $secure,
            $httponly,
            $samesite
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
        return SessionManagerFactory::make();
    }

    protected function getCookieSecure(): bool
    {
        $secure = App::getConfig('app.session.secure');
        $secure = $secure ?? App::getConfig('app.session_secure', false);

        return filter_var($secure, FILTER_VALIDATE_BOOLEAN);
    }

    protected function getCookieHttpOnly(): bool
    {
        $httponly = App::getConfig('app.session.httponly');
        $httponly = $httponly ?? App::getConfig('app.session_httponly', true);

        return filter_var($httponly, FILTER_VALIDATE_BOOLEAN);
    }

    protected function getCookieSameSite(): string
    {
        return (string) (
            App::getConfig('app.session.samesite')
                ?: App::getConfig('app.session_samesite', 'Lax')
        );
    }

    /**
     * Check if a client-provided session id matches Cube's generated id format.
     *
     * @param string $id Session id from the request cookie
     * @return bool
     */
    protected function isValidSessionId(string $id): bool
    {
        return strlen($id) === 60 && ctype_xdigit($id);
    }
}
