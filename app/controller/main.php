<?php
/**
 * Контроллер главной страницы.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template', 'auth');

/**
 * Главная страница
 */
function controller_main()
{
    $user = auth_get_current_user();

    if ($user) {
        $path = auth_get_default_url($user);

        if ($path && $path !== '/') {
            response_redirect($path);

            return;
        }
    }

    response_send(template_render('main'));
}
