<?php
/**
 * Админка.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template', 'auth', 'validate', 'security', 'billing');
require_repos('orders', 'users');

/**
 * Главная страница админки.
 */
function controller_admin()
{
    // авторизация
    $user = auth_get_current_user();

    if (__controller_admin_deny_access($user, APP_ROLE_ADMIN)) {
        return;
    }

    response_send(template_render('admin'));
}


/**
 * В случае отсутствия доступа посылает код ошибки или перенаправления клиенту.
 *
 * @param array $user модель пользователя
 * @param int   $role требуемая роль
 * @return bool true, если доступ закрыт, false, если доступ разрешён
 *
 * @internal
 *
 * @todo перенести в хелперы, в orders лежит что-то очень похожее
 */
function __controller_admin_deny_access($user, $role)
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
