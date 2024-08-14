<?php

use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Http\Uri;
use Cube\View\ViewRenderer;

/**
 * Http functions here
 * 
 * =============================================================
 * Methods related to http requests and response should go here
 * =============================================================
 */

/**
 * Redirect path
 *
 * @param string $path
 * @param boolean $is_external
 * @return Response
 */
function redirect($path, $params = [], $is_external = false)
{
    return response()->redirect($path, $params, $is_external);
}

/**
 * Redirect to previous url
 *
 * @return Response
 */
function back(): Response
{
    $request = Request::getCurrentRequest();
    $ref = $request->getServer()->get('http_referer');
    $host = $request->getServer()->get('http_host');

    if (!$ref) {
        return $host;
    }

    $uri = new Uri($ref);
    $rdr_uri = ($host === $uri->getHost()) ? $ref : $request->url()->getFullUrl();
    return redirect($rdr_uri, [], true);
}

/**
 * Create a response instance
 * 
 * @param boolean $new_instance Set if a new instance of response is needed
 * @return Response
 */
function response(): Response
{
    return new Response();
}

/**
 * Render a view or compile view to string
 *
 * @param string $tpl Template to load
 * @param array $context View context
 * @param boolean $run_render Whether to run render compiled view or return as string
 * @param boolean $new_instance Set if a new instance of response is needed
 * @return Response
 */
function view($tpl, $context = [])
{
    return load_view($tpl, $context);
}

/**
 * Load view as string
 *
 * @param string $tpl
 * @param array $context
 * @return Response
 */
function load_view($tpl, array $context = []): string
{
    $renderer = new ViewRenderer();
    return $renderer->render($tpl, $context);
}
