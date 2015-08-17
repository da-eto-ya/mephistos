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
    // авторизация
    $user = auth_get_current_user();

    if (__controller_orders_deny_access($user, APP_ROLE_EXECUTOR)) {
        return;
    }

    // TODO: get orders list

    response_send(template_render('orders/list'));
}

/**
 * Создание заказа.
 */
function controller_orders_create()
{
    // авторизация
    $user = auth_get_current_user();

    if (__controller_orders_deny_access($user, APP_ROLE_CUSTOMER)) {
        return;
    }

    // order form
    $order = [
        'price' => '',
        'description' => '',
    ];
    $errors = [];
    $createdOrder = [];

    if (request_is_post()) {
        $order['price'] = _p('price', 0, APP_PARAM_FLOAT);
        $order['description'] = _p('description', '');

        $errors = validate_fields($order, [
            'price' => [
                ['required', 'msg' => 'Введите стоимость'],
                ['regex', 'params' => '/^[0-9]+(?:[,\.][0-9]{2})?$/', 'msg' => 'Введите сумму в формате «123.45»'],
                ['range', 'params' => [1, 10001], 'msg' => 'Введите сумму от 1 до 10000'],
            ],
            'description' => [
                ['required', 'msg' => 'Введите описание'],
                ['max_length', 'params' => 665, 'Длина описания не должна превышать 665 символов'],
            ],
        ]);

        if (empty($errors)) {
            // TODO: save to db
            $createdOrder = $order;
            $order = [
                'price' => '',
                'description' => '',
            ];
        }
    }

    response_send(template_render('orders/create', [
        'order' => $order,
        'errors' => $errors,
        'createdOrder' => $createdOrder,
    ]));
}

/**
 * В случае отсутствия доступа посылает код перенаправления клиенту.
 *
 * @param array $user модель пользователя
 * @param int   $role требуемая роль
 * @return bool true, если доступ закрыт, false, если доступ разрешён
 *
 * @internal
 */
function __controller_orders_deny_access($user, $role)
{
    if (!$user) {
        response_redirect('/');

        return true;
    }

    if (!auth_is_authorized_access_allowed($user, [$role])) {
        response_forbidden();

        return true;
    }

    return false;
}
