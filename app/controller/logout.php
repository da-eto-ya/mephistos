<?php
/**
 * Контроллер логаута.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template', 'auth');

/**
 * Логаут.
 */
function controller_logout()
{
    $user = auth_get_current_user();

    if (!$user) {
        response_redirect('/');

        return;
    }

    $request = request_get_current();

    if (request_is_post($request) && auth_validate_csrf(_p('_csrf', ''), ['logout'])) {
        auth_stop_authorized_session();
        response_redirect('/');

        return;
    }

    response_send(template_render('logout'));
}
