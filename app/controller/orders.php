<?php
/**
 * Контроллер заказов.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template', 'auth', 'validate', 'security', 'billing');
require_repos('orders');

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

    $limit = 10;
    $orders = repo_orders_get_list($limit);
    $customerIds = array_column($orders, 'customer_id');
    $customers = repo_users_get_by_ids($customerIds);

    response_send(template_render('orders/list', [
        'orders' => $orders,
        'customers' => $customers,
    ]));
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
    $createErrors = [];

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
            $orderId = repo_orders_create(
                billing_format_dollars_as_cents($order['price']),
                $order['description'],
                $user['id']
            );

            if ($orderId !== false) {
                $createdOrder = repo_orders_get_one_by_id($orderId);
                $order = [
                    'price' => '',
                    'description' => '',
                ];
            } else {
                $createErrors = ['Не удалось добавить заказ. Попробуйте позже.'];
            }
        }
    }

    response_send(template_render('orders/create', [
        'order' => $order,
        'errors' => $errors,
        'createdOrder' => $createdOrder,
        'createErrors' => $createErrors,
    ]));
}

/**
 * Исполнение заказа.
 */
function controller_orders_execute()
{
    // авторизация
    $user = auth_get_current_user();

    if (__controller_orders_deny_access($user, APP_ROLE_EXECUTOR)) {
        return;
    }

    // неверный http verb
    if (!request_is_post()) {
        response_change_method('POST');

        return;
    }

    $id = _p('id', 0, APP_PARAM_INT);

    if (!$id) {
        return;
    }

    /*$success = */billing_order_execute($id, $user);
    // TODO: message or json result

    response_redirect(router_get_path('orders', 'list'));
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
