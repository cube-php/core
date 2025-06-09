<?php

use Cube\Http\Response;
use Cube\Http\Session;
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
    $history = Session::get('cubeHttpUrlHistory');

    if (!$history) {
        return response()->redirect('/');
    }

    $url = $history[count($history) - 1] ?? env('app_url');
    return redirect($url, [], true);
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
