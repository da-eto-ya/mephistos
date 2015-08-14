<?php
/**
 * Контроллер авторизации.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template', 'auth', 'validate');

/**
 * Логин.
 */
function controller_login()
{
    $credentials = [
        'username' => '',
        'password' => '',
    ];
    $error = '';

    if (request_is_post()) {
        $credentials['username'] = _p('username', '');
        $credentials['password'] = _p('password', '');

        $validateErrors = validate_fields($credentials, [
            'username' => [
                ['required'],
                ['max_length', 'params' => 255],
                ['regex', 'params' => '/^[a-zA-Z][-_a-zA-Z0-9]*$/'],
            ],
            'password' => [
                ['required'],
            ],
        ]);

        if (!empty($validateErrors)) {
            $error = "Неправильный логин или пароль";
        } else {
            // TODO: check auth
        }
    }

    response_send(template_render('login', [
        'credentials' => $credentials,
        'error' => $error,
    ]));
}
