<?php
/**
 * Контроллер авторизации.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template', 'auth', 'validate', 'security', 'router');

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

    // первичная аутентификация и, при успехе, переход на другой URL
    $user = auth_get_current_user();

    if ($user) {
        response_redirect(auth_get_default_url($user));

        return;
    }

    if (request_is_post()) {
        $error = "Неверный логин или пароль";
        $credentials['username'] = _p('username', '');
        $credentials['password'] = _p('password', '');

        $validateErrors = validate_fields($credentials, [
            'username' => [
                ['required'],
                ['max_length', 'params' => APP_USER_USERNAME_MAXLENGTH],
                ['regex', 'params' => '/^[a-zA-Z][-_a-zA-Z0-9]*$/'],
            ],
            'password' => [
                ['required'],
            ],
        ]);

        if (empty($validateErrors) && false !== ($user = auth_find_user($credentials))) {
            // если изменился алгоритм или параметры шифрования, то обновляем хэш
            if (security_password_needs_rehash($user['password_hash'])) {
                auth_rehash_user_password($user['id'], $credentials['password']);
            }

            auth_start_authorized_session($user['id']);
            response_redirect(auth_get_default_url($user));

            return;
        }
    }

    if (request_is_ajax()) {
        response_json([
            'error' => $error,
        ]);
    } else {
        response_send(template_render('login', [
            'credentials' => $credentials,
            'error' => $error,
        ]));
    }
}
