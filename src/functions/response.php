<?php

use Cube\Http\Response;

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
function redirect($path, $params = [], $is_external = false) {
    return response()->redirect($path, $params, $is_external);
}

/**
 * Create a response instance
 * 
 * @param boolean $new_instance Set if a new instance of response is needed
 * @return Response
 */
function response($new_instance = false) {
    return (Response::getInstance($new_instance));
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
function view($tpl, $context = [], $run_render = true, $new_instance = false) {
    return response($new_instance)->view($tpl, $context, $run_render);
}