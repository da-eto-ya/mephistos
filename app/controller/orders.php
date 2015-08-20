<?php
/**
 * Контроллер заказов.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template', 'auth', 'validate', 'security', 'billing');
require_repos('orders', 'users');

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

    $from = _g('from', '');
    $fromRand = _g('fromRand', 0, APP_PARAM_INT);

    if (!$from || !validate_db_datetime($from)) {
        $from = date(APP_DB_DATE_FORMAT, time() + 1);
    }

    $orders = repo_orders_get_list($limit, $from, $fromRand);
    $customers = repo_users_get_by_ids(array_column($orders, 'customer_id'));

    // TODO: move inline one model to another to helper or repo
    foreach ($orders as $key => $order) {
        if ($order['customer_id'] && isset($customers[$order['customer_id']])) {
            $orders[$key]['customer'] = array_restrict($customers[$order['customer_id']], ['id', 'username', 'avatar']);
        } else {
            $orders[$key]['customer'] = [];
        }

        $orders[$key]['price_dollar'] = billing_format_cents_as_dollars($order['price']);
        $orders[$key]['_csrf'] = auth_get_csrf(['orders', 'execute', $order['id']]);
    }

    $lastOrder = $orders ? $orders[count($orders) - 1] : false;
    $next = $lastOrder ? $lastOrder['created'] : '';
    $nextRand = $lastOrder ? $lastOrder['created_rand'] : 0;

    $result = [
        'orders' => $orders,
        'next' => $next,
        'nextRand' => $nextRand,
    ];

    if (request_is_ajax()) {
        response_json($result);
    } else {
        response_send(template_render('orders/list', $result));
    }
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
        if (!auth_validate_csrf(_p('_csrf', ''), ['orders', 'create'])) {
            $createErrors[] = 'Похоже, у вас закончилась сессия или ваш аккаунт пытаются использовать мошенники.';
            $createErrors[] = 'Для корректной работы с сайтом обновите страницу или перелогиньтесь.';
            // TODO: подумать, может, ввести код ошибки для ajax и обрабатывать csrf отдельно
        } else {
            $order['price'] = _p('price', 0, APP_PARAM_FLOAT);
            $order['description'] = _p('description', '');

            $errors = validate_fields($order, [
                'price' => [
                    ['required', 'msg' => 'Введите стоимость'],
                    ['regex', 'params' => '/^[0-9]+(?:[,\.][0-9]{2})?$/', 'msg' => 'Введите сумму в формате 123.45'],
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
                    $createdOrder['price_dollar'] = billing_format_cents_as_dollars($createdOrder['price']);
                    $order = [
                        'price' => '',
                        'description' => '',
                    ];
                } else {
                    $createErrors = ['Не удалось добавить заказ. Попробуйте позже.'];
                }
            }
        }
    }

    $result = [
        'order' => $order,
        'errors' => $errors,
        'createdOrder' => $createdOrder,
        'createErrors' => $createErrors,
    ];

    if (request_is_ajax()) {
        response_json($result);
    } else {
        response_send(template_render('orders/create', $result));
    }
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
    $success = false;
    $balance = false;

    if (!$id) {
        $error = 'Ошибка соединения.';
    } else if (!auth_validate_csrf(_p('_csrf', ''), ['orders', 'execute', $id])) {
        // TODO: отдельный код для csrf
        $error = 'Ошибка соединения.';
    } else {
        $success = billing_order_execute($id, $user);
        $balance = $success ? repo_users_get_balance($user['id']) : false;
        $error = $success ? '' : 'Не удалось исполнить заказ';
    }

    if (false !== $balance) {
        $balance = billing_format_cents_as_dollars($balance);
    }

    if (request_is_ajax()) {
        response_json([
            'success' => $success,
            'error' => $error,
            'balance' => $balance,
        ]);
    } else {
        response_location(router_get_path('orders', 'list'));
    }
}

/**
 * В случае отсутствия доступа посылает код ошибки или перенаправления клиенту.
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

    if (!auth_user_has_role($user, [$role])) {
        response_forbidden();

        return true;
    }

    return false;
}
