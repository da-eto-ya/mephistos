<?php
/**
 * Контроллер авторизации.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template', 'auth', 'validate', 'security');

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

    // TODO: здесь ещё нужна первичная аутентификация и, при успехе, переход на другой URL

    if (request_is_post()) {
        $error = "Неверный логин или пароль";
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

        if (empty($validateErrors) && false !== ($user = auth_find_user($credentials))) {
            $error = '';

            // если изменился алгоритм или параметры шифрования, то обновляем хэш
            if (security_password_needs_rehash($user['hash'])) {
                auth_rehash_user_password($user['id'], $credentials['password']);
            }

            // TODO: установка кук
            auth_set_access_token($user['id']);
            response_redirect('/list.html');
            return;
        }
    }

    response_send(template_render('login', [
        'credentials' => $credentials,
        'error' => $error,
    ]));
}
