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
    ], false);
}
