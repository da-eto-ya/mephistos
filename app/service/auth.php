<?php
/**
 * Сервис аутентификации и идентификации пользователей.
 */

require_once __DIR__ . '/../require.php';
require_services('security');
require_modules('repo/users');

/**
 * Находит пользователя, подходящего под указанные credentials.
 *
 * @param array $credentials
 * @return mixed false в том случае, если такой пользователь не найден
 *
 * @todo может, отвязаться от модели пользователя и возвращать ID?
 */
function auth_find_user($credentials)
{
    if (empty($credentials['username']) || empty($credentials['password'])) {
        return false;
    }

    $user = repo_users_get_by_username($credentials['username']);

    if (!$user || !security_password_verify($credentials['password'], $user['hash'])) {
        return false;
    }

    return $user;
}

/**
 * Обновляет хэш пароля для пользователя.
 *
 * @param int $uid
 * @param string $password
 * @return bool|int
 */
function auth_rehash_user_password($uid, $password)
{
    return repo_users_update_one($uid, ['hash' => security_password_hash($password)]);
}

function auth_set_access_token($uid)
{
    // TODO: всё
    return ;
}
