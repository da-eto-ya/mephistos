<?php
/**
 * Сервис работы с БД.
 */

require_once __DIR__ . '/../require.php';
require_services('log');

/** Формат даты в БД */
const APP_DB_DATE_FORMAT = 'Y-m-d H:i:s';

/**
 * Устанавливает или возвращает конфигурацию БД.
 *
 * @param array|null $config массив для установки или null для возврата ранее сохранённого значения
 * @return array
 */
function db_config(array $config = null)
{
    static $_config = [
        'default' => false,
        'lookup' => [],
    ];

    if (null !== $config) {
        if (isset($config['connections'])) {
            $connections = [];
            $defaults = [
                'host' => '127.0.0.1',
                'port' => '3306',
                'username' => 'root',
                'password' => '',
                'encoding' => 'utf8',
            ];

            foreach ($config['connections'] as $name => $conn) {
                if (isset($conn['database'])) {
                    $connections[$name] = array_merge($defaults, $conn);
                } else {
                    log_critical("Can't determine DB connection with name " . $name, $conn);
                }
            }

            if (!count($connections)) {
                log_critical("Can't find correct DB connections", $config);
            } else {
                $_config['connections'] = $connections;

                // соединение по умолчанию, если не задано - первое из найденных
                if (isset($config['default']) && isset($_config['connections'][$config['default']])) {
                    $_config['default'] = $config['default'];
                } else {
                    $_config['default'] = array_keys($_config['connections'])[0];
                }

                // соответствия таблиц
                if (isset($config['tables'])) {
                    $lookup = [];

                    foreach ($config['tables'] as $connection => $tables) {
                        if (isset($_config['connections'][$connection])) {
                            foreach ($tables as $table) {
                                $lookup[$table] = $connection;
                            }
                        }
                    }

                    $_config['lookup'] = $lookup;
                }
            }
        } else {
            log_critical("Can't find DB connections", $config);
        }
    }

    return $_config;
}

/**
 * Возвращает соединение с данным именем.
 *
 * @param string $name
 * @return object|bool false в случае ошибки, иначе - объект соединения
 */
function db_connection($name)
{
    static $connections = [];

    if (!isset($connections[$name])) {
        $config = db_config();

        if (!isset($config['connections'][$name])) {
            log_critical("Can't find connection with name " . $name, $config);

            return false;
        }

        $conn = $config['connections'][$name];
        $link = mysqli_connect($conn['host'], $conn['username'], $conn['password'], $conn['database'], $conn['port']);

        if (!$link) {
            $code = mysqli_connect_errno();
            $error = mysqli_connect_error();
            log_alert("DB connection error: ({$code}) {$error}");

            return false;
        }

        if (!mysqli_set_charset($link, $conn['encoding'])) {
            log_alert("DB set encoding error", [$name, $conn['encoding']]);

            return false;
        }

        $connections[$name] = $link;
    }

    return $connections[$name];
}

/**
 * Возвращает имя соединения для данной таблицы или false в случае ошибки.
 *
 * @param string $table
 * @return string|bool
 */
function db_lookup($table)
{
    static $lookup = [];

    if (!isset($lookup[$table])) {
        $config = db_config();

        if (isset($config['lookup'][$table])) {
            $lookup[$table] = $config['lookup'][$table];
        } else {
            if ($config['default']) {
                $lookup[$table] = $config['default'];
            } else {
                log_error("DB lookup_table error: can't find `{$table}`");

                return false;
            }
        }
    }

    return $lookup[$table];
}

/**
 * Небезопасный прямой запрос к БД без привязки параметров.
 * Использовать только для не изменяющихся запросов.
 *
 * @param object $connection
 * @param string $query
 * @return bool|object
 */
function db_query_raw_unsafe($connection, $query)
{
    /** @var mysqli $connection */
    $result = mysqli_query($connection, $query);

    if (false === $result) {
        $code = mysqli_errno($connection);
        $error = mysqli_error($connection);
        log_error("DB query_raw error: ({$code}) {$error}");

        return false;
    }

    return $result;
}

/**
 * Создание подготовленного запроса.
 * Прямой запрос к БД по линку соединения.
 *
 * @param object $connection
 * @param string $query
 * @return bool|object
 */
function db_prepare_raw($connection, $query)
{
    /** @var mysqli $connection */
    $statement = mysqli_prepare($connection, $query);

    if (false === $statement) {
        $code = mysqli_errno($connection);
        $error = mysqli_error($connection);
        log_error("DB prepare_raw error: ({$code}) {$error}");

        return false;
    }

    return $statement;
}

/**
 * Создание подготовленного запроса.
 *
 * @param string $table
 * @param string $query
 * @return bool|object
 */
function db_prepare($table, $query)
{
    if (!($connection = db_connection(db_lookup($table)))) {
        return false;
    }

    return db_prepare_raw($connection, $query);
}

/**
 * Привязка параметров к подготовленному запросу.
 *
 * @param object $statement
 * @param array  $params
 * @return bool
 */
function db_stmt_bind_params($statement, array $params = [])
{
    if (!$statement) {
        return false;
    }

    if (!count($params)) {
        return true;
    }

    $types = [];

    foreach ($params as $value) {
        if (is_int($value) || is_bool($value)) {
            $types[] = 'i';
        } else if (is_float($value) || is_double($value)) {
            $types[] = 'd';
        } else if ('blob' == gettype($value)) {
            $types[] = 'b';
        } else {
            $types[] = 's';
        }
    }

    $callParams = [];
    $callParams[] = $statement;
    $callParams[] = join('', $types);

    foreach ($params as $i => $value) {
        $callParams[] = &$params[$i];
    }

    return call_user_func_array('mysqli_stmt_bind_param', $callParams);
}

/**
 * Исполнение подготовленного запроса.
 *
 * @param object $statement
 * @return bool
 */
function db_stmt_execute($statement)
{
    /** @var mysqli_stmt $statement */
    if (!$statement) {
        return false;
    }

    $result = mysqli_stmt_execute($statement);

    if (false === $result) {
        $code = mysqli_stmt_errno($statement);
        $error = mysqli_stmt_error($statement);
        log_warning("Can't execute prepared statement; error: ({$code}) {$error}");
    }

    return $result;
}

/**
 * Получение объекта результата из подготовленного запроса.
 *
 * @param object $statement
 * @return bool|object
 */
function db_stmt_result($statement)
{
    /** @var mysqli_stmt $statement */
    if (!$statement) {
        return false;
    }

    return mysqli_stmt_get_result($statement);
}

/**
 * Возвращает количество строк, измененных запросом INSERT, UPDATE или DELETE.
 *
 * @param object $statement
 * @return bool|int|string
 */
function db_stmt_affected_rows($statement)
{
    if (!$statement) {
        return false;
    }

    /** @var mysqli_stmt $statement */
    $result = mysqli_stmt_affected_rows($statement);

    if (null === $result) {
        return false;
    }

    return $result;
}

/**
 * Получает число строк, затронутых последним запросом.
 *
 * @param object $connection
 * @return bool|int
 */
function db_affected_rows_raw($connection)
{
    if (!$connection) {
        return false;
    }

    /** @var mysqli $connection */
    $result = mysqli_affected_rows($connection);

    if ($result < 0) {
        return false;
    }

    return $result;
}

/**
 * Получает число строк, затронутых последним запросом.
 *
 * @param string $table
 * @return bool|int
 */
function db_affected_rows($table)
{
    if (!($connection = db_connection(db_lookup($table)))) {
        return false;
    }

    return db_affected_rows_raw($connection);
}

/**
 * Запрос с привязкой параметров.
 *
 * @param string $table
 * @param string $query
 * @param array  $params
 * @return bool|object false в случае ошибки
 */
function db_query($table, $query, array $params = [])
{
    $statement = db_prepare($table, $query);
    db_stmt_bind_params($statement, $params);
    db_stmt_execute($statement);

    return db_stmt_result($statement);
}

/**
 * Запрос на изменение данных с привязкой параметров.
 *
 * @param string $table
 * @param string $query
 * @param array  $params
 * @return bool|int false в случае ошибки, иначе - количество затронутых строк
 */
function db_exec($table, $query, array $params = [])
{
    $statement = db_prepare($table, $query);
    db_stmt_bind_params($statement, $params);
    db_stmt_execute($statement);

    return db_stmt_affected_rows($statement);
}

/**
 * Получение строки результата в виде нумерованного массива.
 *
 * @param object $result
 * @return array|null null, если строк больше нет
 */
function db_fetch_row($result)
{
    /** @var mysqli_result $result */
    return mysqli_fetch_row($result);
}

/**
 * Получение строки результата в виде ассоциативного массива.
 *
 * @param object $result
 * @return array|null null, если строк больше нет
 */
function db_fetch_assoc($result)
{
    /** @var mysqli_result $result */
    return mysqli_fetch_assoc($result);
}

/**
 * Получение всех строк результата в виде ассоциативных массивов.
 *
 * @param object $result
 * @return array
 */
function db_fetch_all($result)
{
    /** @var mysqli_result $result */
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Освобождает ресурсы результата.
 *
 * @param object $result
 */
function db_free_result($result)
{
    /** @var mysqli_result $result */
    mysqli_free_result($result);
}

/**
 * Возвращает автоматически генерируемый ID, используя последний запрос.
 *
 * @param object $connection
 * @return bool|int
 */
function db_inserted_id_raw($connection)
{
    if (!$connection) {
        return false;
    }

    /** @var mysqli $connection */

    return mysqli_insert_id($connection);
}

/**
 * Возвращает автоматически генерируемый ID, используя последний запрос.
 *
 * @param string $table
 * @return bool|int
 */
function db_inserted_id($table)
{
    if (!($connection = db_connection(db_lookup($table)))) {
        return false;
    }

    return db_inserted_id_raw($connection);
}

/**
 * Запрос и получение одной строки в виде ассоциативного массива.
 *
 * @param string $table
 * @param string $query
 * @param array  $params
 * @return array|bool false в случае пустого результата или ошибки
 */
function db_get_one($table, $query, array $params = [])
{
    $result = db_query($table, $query, $params);

    if (false === $result) {
        return false;
    }

    $row = db_fetch_assoc($result);
    db_free_result($result);

    return $row ?: false;
}

/**
 * Запрос и получение одной строки в виде ассоциативного массива по уникальному полю.
 * Небезопасный метод, полагается на правильно переданные имена таблицы и полей.
 *
 * @param string $table
 * @param string $idField
 * @param mixed  $idValue
 * @return array|bool false в случае пустого результата или ошибки
 */
function db_get_one_unsafe($table, $idField, $idValue)
{
    $result = db_query($table, "SELECT * FROM `{$table}` WHERE `{$idField}` = ? LIMIT 1", [$idValue]);

    if (false === $result) {
        return false;
    }

    $row = db_fetch_assoc($result);
    db_free_result($result);

    return $row ?: false;
}

/**
 * Обновление одной строки.
 * Небезопасный метод, полагается на правильно переданные имена таблицы и полей.
 *
 * @param string $table
 * @param array  $fields поля для обновления [имя => значение]
 * @param string $idField имя идентификатора
 * @param mixed  $idValue значение идентификатора
 * @param array  $allowedFields разрешённые для обновления поля [имя => true]
 * @return bool|int
 */
function db_update_one_unsafe($table, array $fields, $idField, $idValue, array $allowedFields = [])
{
    $setFields = [];
    $params = [];

    foreach ($fields as $field => $value) {
        if (!empty($allowedFields[$field])) {
            // TODO: quote backticks?
            $setFields[] = "`$field` = ?";
            $params[] = $value;
        } else {
            break;
        }
    }

    // нечего обновлять или не все поля удалось найти в разрешённых
    if (!count($setFields) || count($fields) !== count($setFields)) {
        return false;
    }

    $set = join(', ', $setFields);
    $params[] = $idValue;

    return db_exec($table, "UPDATE `{$table}` SET {$set} WHERE `{$idField}` = ? LIMIT 1", $params);
}

/**
 * Добавление одной строки.
 * Небезопасный метод, полагается на правильно переданные имена таблицы и полей.
 *
 * @param string $table
 * @param array  $fields значения полей модели [имя => значение]
 * @param array  $allowedFields хэш разрешённых полей [имя => true]
 * @param array  $requiredFields хэш обязательных полей [имя => true]
 * @return bool|int
 */
function db_insert_one_unsafe($table, array $fields, array $allowedFields, array $requiredFields)
{
    $setFields = [];
    $required = [];
    $params = [];

    foreach ($fields as $field => $value) {
        if (!empty($allowedFields[$field])) {
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
    foreach (array_keys($requiredFields) as $field) {
        if (!isset($required[$field])) {
            return false;
        }
    }

    $names = join(', ', $setFields);
    $placeholders = join(', ', array_fill(0, count($params), '?'));
    $affected = db_exec($table, "INSERT INTO `{$table}` ({$names}) VALUES ({$placeholders})", $params);

    if ($affected != 1) {
        return false;
    }

    return db_inserted_id($table);
}

/**
 * Выполнить запрос и возвратить все.
 *
 * @param string $table имя таблицы
 * @param string $sql строка запроса
 * @param array  $params параметры для запроса
 * @return array|bool false в случае ошибки, массив строк в случае успеха
 */
function db_get_all($table, $sql, array $params = [])
{
    $result = db_query($table, $sql, $params);

    if (!$result) {
        return false;
    }

    return db_fetch_all($result);
}

/**
 * Получить все записи, подпадающие под условие.
 *
 * @param string $table имя таблицы
 * @param string $clause условие после 'SELECT * FROM `{$table}`'
 * @param array  $params параметры для запроса
 * @return array|bool false в случае ошибки, массив строк в случае успеха
 */
function db_get_all_where($table, $clause, array $params = [])
{
    return db_get_all($table, "SELECT * FROM `{$table}` {$clause}", $params);
}
