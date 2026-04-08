<?php

namespace Cube\Http\Session;

use Cube\App\App;
use Cube\Http\Response;
use Cube\Http\Session;
use Cube\Http\Session\Stores\ArraySessionStore;
use Cube\Http\Session\Stores\DatabaseSessionStore;
use Cube\Http\Session\Stores\FileSessionStore;
use Cube\Interfaces\RequestInterface;
use InvalidArgumentException;

class SessionManager
{
    protected string $cookie_name = 'CUBE_SESSION_DX';

    protected int $lifetime = 7200;

    public function __construct(protected SessionStoreInterface $store) {}

    public function start(RequestInterface $request): SessionHandler
    {
        $id = $request->getCookies()->get($this->cookie_name);
        $data = $this->store->read($id);

        if (!$id) {
            $id = generate_token(30);
            return new SessionHandler($id, $data);
        }

        return new SessionHandler($id, $data);
    }

    public function persist(SessionHandler $session, Response $response)
    {
        if ($session->isChanged()) {
            $this->store->write(
                $session->id(),
                $session->all(),
                $this->lifetime
            );
        }

        $response->withCookie(
            $this->cookie_name,
            $session->id(),
            time() + $this->lifetime,
            '/',
            false,
            true
        );
    }

    public function destroy(SessionHandler $session, Response $response)
    {
        $this->store->destroy($session->id());

        $response->withCookie(
            $this->cookie_name,
            '',
            time() - 3600,
            '/',
            false,
            true
        );
    }

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
