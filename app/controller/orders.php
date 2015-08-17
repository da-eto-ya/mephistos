<?php
/**
 * Контроллер заказов.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template', 'auth', 'validate', 'security');

/**
 * Список заказов.
 */
function controller_orders_list()
{
    // TODO
    response_send(template_render('orders/list'));
}

/**
 * Создание заказа
 */
function controller_orders_create()
{
    // TODO
    response_send(template_render('orders/create'));
}
