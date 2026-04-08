<?php

namespace Cube\Helpers\Response;

use Cube\Http\Cookie;
use Cube\Interfaces\ResponseInterface;

class ResponseEmitter
{
    /**
     * Cube default response emitter
     *
     * @param ResponseInterface $response
     */
    public function __construct(public ResponseInterface $response) {}

    /**
     * Emit respinse
     *
     * @return void
     */
    public function emit()
    {
        $this->emitCookies();
        $this->emitHeaders();
        $this->emitStatusLine();
        $this->emitBody();
    }

    /**
     * Emit cookies
     *
     * @return void
     */
    private function emitCookies()
    {
        $queue = $this->response->getCookies();
        every($queue, function ($content) {
            setcookie(
                $content->name,
                $content->value,
                [
                    'expires' => $content->expires,
                    'path' => $content->path,
                    'domain' => $content->domain,
                    'secure' => $content->secure,
                    'httponly' => $content->httponly,
                    'samesite' => $content->samesite
                ]
            );
        });
    }

    /**
     * Emit headers
     *
     * @return void
     */
    private function emitHeaders()
    {
        if (headers_sent()) {
            return;
        }

        $headers = $this->response->getHeaders();
        every($headers, function ($value, $key) {
            header($key . ': ' . $value);
        });
    }

    /**
     * Emit status header
     *
     * @return void
     */
    private function emitStatusLine()
    {
        $status_line = sprintf(
            'HTTP/%s %s %s',
            $this->response->getProtocol(),
            $this->response->getHttpStatusCode(),
            $this->response->getHttpReason()
        );

        header($status_line);
    }

    /**
     * Emit body
     *
     * @return void
     */
    private function emitBody()
    {
        echo $this->response->getBody();
    }
}
