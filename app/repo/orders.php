<?php
/**
 * Репозиторий работы с заказами.
 */

require_once __DIR__ . '/../require.php';
require_services('db', 'validate');

/** Новый заказ */
const APP_ORDER_STATUS_NEW = 0;
/** Исполненный заказ */
const APP_ORDER_STATUS_EXECUTED = 1;

/**
 * Список заказов с какой-либо даты.
 *
 * @param int         $limit максимальное количество строк в результате
 * @param string|bool $fromTime false для текущего времени
 * @param int         $fromRand метка создания последнего объекта, которого ещё не должно быть в выборке
 *                              (последний из предыдущего набора)
 * @return array массив строк
 */
function repo_orders_get_list($limit, $fromTime = false, $fromRand = 0)
{
    if (!$fromTime) {
        $fromTime = date(APP_DB_DATE_FORMAT, time() + 1);
    } else {
        $fromTime = (string) $fromTime;

        if (!validate_db_datetime($fromTime)) {
            return [];
        }
    }

    $limit = (int) $limit;

    if ($limit <= 0) {
        return [];
    }

    $fromRand = (int) $fromRand;

    return db_get_all_where(
        'orders',
        "WHERE
            (status = ? AND created = ? AND created_rand < ?) OR
            (status = ? AND created < ?)
        ORDER BY created DESC, created_rand DESC
        LIMIT ?",
        [APP_ORDER_STATUS_NEW, $fromTime, $fromRand, APP_ORDER_STATUS_NEW, $fromTime, $limit]
    ) ?: [];
}

/**
 * Создание заказа.
 *
 * @param int    $price
 * @param string $description
 * @param int    $customerId
 * @return bool|int
 */
function repo_orders_create($price, $description, $customerId)
{
    return repo_orders_insert_one([
        'price' => (int) $price,
        'description' => (string) $description,
        'customer_id' => (int) $customerId,
    ]);
}

/**
 * Получить заказ по ID.
 *
 * @param int $id
 * @return array|bool
 */
function repo_orders_get_one_by_id($id)
{
    $id = (int) $id;

    if (!$id) {
        return false;
    }

    return db_get_one_unsafe('orders', 'id', $id);
}

/**
 * Обновить данные одного заказа.
 *
 * @param int   $id
 * @param array $fields
 * @return bool|int
 */
function repo_orders_update_one($id, array $fields)
{
    $id = (int) $id;

    if (!$id || !$fields) {
        return false;
    }

    if (!empty(repo_orders_validate_fields($fields))) {
        return false;
    }

    return db_update_one_unsafe('orders', $fields, 'id', $id, __repo_orders_allowed_fields());
}

/**
 * Добавить один заказ.
 *
 * @param array $fields
 * @return bool|int
 */
function repo_orders_insert_one(array $fields)
{
    if (!empty(repo_orders_validate_fields($fields))) {
        return false;
    }

    $fields['created'] = date(APP_DB_DATE_FORMAT);

    // created_rand используется для определения порядка вывода при совпадающих created.
    // чтобы получить вероятность > 1% того, что две записи в одну заданную секунду имеют совпадающий created_rand,
    // нужно иметь RPS (количество новых записей в секунду) > 6500 (по парадоксу о днях рождения).
    // пожалуй, при таком трафике нужно будет пересматривать систему хранения.
    $fields['created_rand'] = mt_rand();

    return db_insert_one_unsafe('orders', $fields, __repo_orders_allowed_fields(), __repo_orders_required_fields());
}

/**
 * Хэш разрешённых для вставки/изменения полей.
 *
 * @return array
 *
 * @internal
 */
function __repo_orders_allowed_fields()
{
    static $_allowedFields = [
        'price' => true,
        'description' => true,
        'customer_id' => true,
        'executor_id' => true,
        'status' => true,
        'created' => true,
        'created_rand' => true,
        'executed' => true,
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
function __repo_orders_required_fields()
{
    static $_requiredFields = [
        'price' => true,
        'description' => true,
        'customer_id' => true,
    ];

    return $_requiredFields;
}

/**
 * Валидация полей перед добавлением/изменением.
 *
 * @param array $fields
 * @return array массив ошибок
 */
function repo_orders_validate_fields(array $fields)
{
    return validate_fields($fields, [
        'price' => [
            ['required'],
            ['is_int'],
        ],
        'description' => [
            ['required'],
            ['max_length', 'params' => 665],
        ],
        'customer_id' => [
            ['required'],
            ['is_int'],
        ],
        'executor_id' => [
            ['is_int'],
        ],
        'status' => [
            ['is_int'],
            ['in_array', 'params' => [[APP_ORDER_STATUS_NEW, APP_ORDER_STATUS_EXECUTED]]],
        ],
        'created' => [
            ['db_datetime'],
        ],
        'executed' => [
            ['db_datetime'],
        ],
    ], false);
}
