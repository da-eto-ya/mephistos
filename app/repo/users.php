<?php
/**
 * Репозиторий работы с пользователями.
 */

require_once __DIR__ . '/../require.php';
require_services('db', 'validate');

/** Исполнитель заказов */
const APP_ROLE_EXECUTOR = 0;
/** Заказчик */
const APP_ROLE_CUSTOMER = 1;

/**
 * Возвращает массив пользователей с данными id, индексированный этими id.
 *
 * @param array $ids
 * @return array
 */
function repo_users_get_by_ids(array $ids = [])
{
    // flip-flip - быстрый способ получить уникальные значения
    $ids = array_flip(array_flip(array_map('intval', $ids)));

    if (!count($ids)) {
        return [];
    }

    $placeholders = join(',', array_fill(0, count($ids), '?'));
    $rows = db_get_all('users', "SELECT * FROM `users` WHERE `id` IN ({$placeholders})", $ids);

    if (!$rows) {
        return [];
    }

    $users = [];

    foreach ($rows as $row) {
        $users[$row['id']] = $row;
    }

    return $users;
}

/**
 * Увеличить баланс пользователя на указанную сумму.
 *
 * @param int $uid
 * @param int $value сумма пополнения
 * @return bool
 */
function repo_users_add_balance($uid, $value)
{
    $uid = (int) $uid;
    $value = (int) $value;

    if (!$uid || !$value) {
        return false;
    }

    $affected = db_exec(
        'users',
        'UPDATE `users` SET `balance` = `balance` + ? WHERE `id` = ? LIMIT 1',
        [$value, $uid]
    );

    return (1 === $affected);
}

/**
 * Уменьшить баланс пользователя на указанную сумму.
 *
 * @param int $uid
 * @param int $value сумма уменьшения
 * @return bool
 */
function repo_users_sub_balance($uid, $value)
{
    $value = (int) $value;

    return repo_users_add_balance($uid, -$value);
}

/**
 * Получает баланс пользователя.
 *
 * @param int $uid
 * @return bool
 */
function repo_users_get_balance($uid)
{
    $user = repo_users_get_one_by_id($uid);

    if (!$user) {
        return false;
    }

    return $user['balance'];
}

/**
 * Получить исполнителя по ID.
 *
 * @param int $uid
 * @return array|bool
 */
function repo_users_get_executor_by_id($uid)
{
    return repo_users_get_one_by_id_and_role($uid, APP_ROLE_EXECUTOR);
}

/**
 * Получить заказчика по ID.
 *
 * @param int $uid
 * @return array|bool
 */
function repo_users_get_customer_by_id($uid)
{
    return repo_users_get_one_by_id_and_role($uid, APP_ROLE_CUSTOMER);
}

/**
 * Получить пользователя по ID и роли.
 *
 * @param $uid
 * @param $role
 * @return array|bool
 */
function repo_users_get_one_by_id_and_role($uid, $role)
{
    $user = repo_users_get_one_by_id($uid);

    // TODO: можно выделить в запрос при необходимости
    if ($user && $user['role'] == $role) {
        return $user;
    }

    return false;
}

/**
 * Получить пользователя по ID.
 *
 * @param int $uid
 * @return array|bool
 */
function repo_users_get_one_by_id($uid)
{
    $uid = (int) $uid;

    if (!$uid) {
        return false;
    }

    return db_get_one_unsafe('users', 'id', $uid);
}

/**
 * Получить пользователя по username.
 *
 * @param string $username
 * @return array|bool
 */
function repo_users_get_one_by_username($username)
{
    return db_get_one_unsafe('users', 'username', (string) $username);
}

/**
 * Обновить данные одного пользователя.
 *
 * @param int   $uid
 * @param array $fields
 * @return bool|int
 */
function repo_users_update_one($uid, array $fields)
{
    $uid = (int) $uid;

    if (!$uid || !$fields) {
        return false;
    }

    if (!empty(repo_users_validate_fields($fields))) {
        return false;
    }

    return db_update_one_unsafe('users', $fields, 'id', $uid, __repo_users_allowed_fields());
}

/**
 * Добавить одного пользователя.
 *
 * @param array $fields
 * @return bool|int
 */
function repo_users_insert_one(array $fields)
{
    if (!empty(repo_users_validate_fields($fields))) {
        return false;
    }

    $fields['created'] = date(APP_DB_DATE_FORMAT);

    return db_insert_one_unsafe('users', $fields, __repo_users_allowed_fields(), __repo_users_required_fields());
}

/**
 * Хэш разрешённых для вставки/изменения полей.
 *
 * @return array
 *
 * @internal
 */
function __repo_users_allowed_fields()
{
    static $_allowedFields = [
        'username' => true,
        'password_hash' => true,
        'balance' => true,
        'avatar' => true,
        'role' => true,
        'created' => true,
    ];

    return $_allowedFields;
}

/**
 * Хэш необходимых для вставки полей.
 *
 * @return array
 *
 * @internal
 */
function __repo_users_required_fields()
{
    static $_requiredFields = [
        'username' => true,
        'password_hash' => true,
    ];

    return $_requiredFields;
}

/**
 * Валидация полей перед добавлением/изменением.
 *
 * @param array $fields
 * @return array массив ошибок
 */
function repo_users_validate_fields(array $fields)
{
    return validate_fields($fields, [
        'username' => [
            ['required'],
            ['max_length', 'params' => 255],
            ['regex', 'params' => '/^[a-zA-Z][-_a-zA-Z0-9]*$/'],
        ],
        'password_hash' => [
            ['required'],
            ['max_length', 'params' => 255],
        ],
        'balance' => [
            ['is_int'],
        ],
        'avatar' => [
            ['max_length', 'params' => 255],
        ],
        'role' => [
            ['is_int'],
            ['in_array', 'params' => [[APP_ROLE_EXECUTOR, APP_ROLE_CUSTOMER]]],
        ],
        'created' => [
            ['db_datetime'],
        ],
    ], false);
}
