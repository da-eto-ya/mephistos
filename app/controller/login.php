<?php
/**
 * Контроллер авторизации.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template');

/**
 * Логин.
 */
function controller_login()
{
    $username = '';
    $password = '';

    if (request_is_post()) {
        $username = _p('username', '');
        $password = _p('password', '');
        // TODO: check login
    }

    response_send(template_render('login', [
        'form' => [
            'username' => $username,
            'password' => $password,
        ],
    ]));
}
