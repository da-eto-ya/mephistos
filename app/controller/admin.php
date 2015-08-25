<?php
/**
 * Админка.
 */

require_once __DIR__ . '/../require.php';
require_services('request', 'response', 'template', 'auth', 'validate', 'billing');

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

    $commission = billing_get_commission();
    $errors = [];
    $success = false;

    if (request_is_post()) {
        $commission = _p('commission', -1, APP_PARAM_INT);

        if (!validate_range($commission, 0, 101)) {
            $errors[] = 'Комиссия должна быть в процентах от 0 до 100';
        } else {
            $success = billing_set_commission($commission);
            $commission = billing_get_commission(true);

            if (!$success) {
                $errors[] = 'Не удалось обновить данные. Попробуйте позже';
            }
        }
    }

    $result = [
        'commission' => $commission,
        'errors' => $errors,
        'success' => $success,
    ];

    if (request_is_ajax()) {
        response_json($result);
    } else {
        response_send(template_render('admin', $result));
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
