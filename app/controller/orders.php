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

    // TODO: order form

    response_send(template_render('orders/create'));
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
