<?php
/**
 * Репозиторий работы с пользователями.
 */

require_once __DIR__ . '/../require.php';
require_services('db');

/**
 * Получить пользователя по ID.
 *
 * @param int $uid
 * @return array|bool
 */
function repo_users_get_by_id($uid)
{
    $uid = (int) $uid;

    if (!$uid) {
        return false;
    }

    return db_get_one('users', 'SELECT * FROM `users` WHERE `id` = ? LIMIT 1', [$uid]);
}

/**
 * Получить пользователя по username.
 *
 * @param string $username
 * @return array|bool
 */
function repo_users_get_by_username($username)
{
    return db_get_one('users', 'SELECT * FROM `users` WHERE `username` = ? LIMIT 1', [(string) $username]);
}

/**
 * Обновить данные одного пользователя.
 *
 * @param int $uid
 * @param array $fields
 * @return bool|int
 */
function repo_users_update_one($uid, array $fields)
{
    // TODO: move common logic to db
    static $_allowedFields = ['username' => true, 'hash' => true];

    $uid = (int) $uid;

    if (!$uid || !$fields) {
        return false;
    }

    $setFields = [];
    $params = [];

    foreach ($fields as $field => $value) {
        if (!empty($_allowedFields[$field])) {
            // TODO: quote backticks?
            $setFields[] = "`$field` = ?";
            $params[] = $value;
        } else {
            break;
        }
    }

    // не все поля удалось найти в разрешённых
    if (count($fields) !== count($setFields)) {
        return false;
    }

    $set = join(', ', $setFields);
    $params[] = $uid;

    return db_exec('users', "UPDATE `users` SET {$set} WHERE `id` = ? LIMIT 1", $params);
}

/**
 * Добавить одного пользователя.
 *
 * @param array $fields
 * @return bool|int
 */
function repo_users_insert_one(array $fields)
{
    // TODO: move common logic to db + check values?
    static $_allowedFields = ['username' => true, 'hash' => true];
    static $_requiredFields = ['username' => true, 'hash' => true];

    $setFields = [];
    $required = [];
    $params = [];

    foreach ($fields as $field => $value) {
        if (!empty($_allowedFields[$field])) {
            // TODO: quote backticks?
            $setFields[] = "`$field`";
            $required[$field] = true;
            $params[] = $value;
        } else {
            break;
        }
    }

    // не все поля удалось найти в разрешённых
    if (count($fields) !== count($setFields)) {
        return false;
    }

    // проверяем, что устанавливаются все необходимые поля
    foreach (array_keys($_requiredFields) as $field) {
        if (!isset($required[$field])) {
            return false;
        }
    }

    $names = join(', ', $setFields);
    $placeholders = join(', ', array_fill(0, count($params), '?'));
    $affected = db_exec('users', "INSERT INTO `users` ({$names}) VALUES ({$placeholders})", $params);

    if ($affected != 1) {
        return false;
    }

    return db_inserted_id('users');
}
