<?php

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template', 'auth', 'validate', 'security');

function controller_orders()
{
    // TODO
}

function controller_orders_list()
{
    // TODO
    response_send(template_render('orders/list'));
}

function controller_orders_create()
{
    // TODO
}
