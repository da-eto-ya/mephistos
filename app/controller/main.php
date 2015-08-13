<?php
/**
 * Контроллер главной страницы.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template');

/**
 * Главная страница
 */
function controller_main_index()
{
    // главная страница доступна только по основному адресу
    if (request_get_current()['path'] !== '/' || count(func_get_args())) {
        response_not_found();

        return;
    }

    response_text(template_render('main/index', [
        'name' => 'Cooper\'ti"no <a href="">XSS</a>',
    ]));
}
