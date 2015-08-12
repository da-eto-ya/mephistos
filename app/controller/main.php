<?php
/**
 * Контроллер главной страницы.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response');

/**
 * Главная страница
 */
function controller_main_index()
{
    $request = request_get_current();

    // главная страница доступна только по основному адресу
    if ($request['path'] !== '/' || count(func_get_args())) {
        response_not_found();
        return;
    }

    // FIXME: test response
    response_text('hello');
}
