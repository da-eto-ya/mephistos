<?php
/**
 * Контроллер главной страницы.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template');

/**
 * Главная страница
 */
function controller_main()
{
    response_text(template_render('main'));
}
