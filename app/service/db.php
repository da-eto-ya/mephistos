<?php
/**
 * Сервис работы с БД.
 */

require_once __DIR__ . '/../require.php';
require_services('log');

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
            log_alert('DB connection error: (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
            return false;
        }

        $connections[$name] = $link;
    }

    return $connections[$name];
}

/**
 * Возвращает имя соединения для данной таблицы или false в случае ошибки.
 *
 * @param string $tableName
 * @return string|bool
 */
function db_lookup($tableName)
{
    static $lookup = [];

    if (!isset($lookup[$tableName])) {
        $config = db_config();

        if (isset($config['lookup'][$tableName])) {
            $lookup[$tableName] = $config['lookup'][$tableName];
        } else if ($config['default']) {
            $lookup[$tableName] = $config['default'];
        } else {
            log_error("DB lookup_table error: can't find `{$tableName}`");
            return false;
        }
    }

    return $lookup[$tableName];
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
        log_error('DB query_raw error: (' . mysqli_errno($connection) . ') ' . mysqli_error($connection));
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
        log_error('DB prepare_raw error: (' . mysqli_errno($connection) . ') ' . mysqli_error($connection));
        return false;
    }

    return $statement;
}

/**
 * Создание подготовленного запроса.
 *
 * @param string $tableName
 * @param string $query
 * @return bool|object
 */
function db_prepare($tableName, $query)
{
    if (!($connection = db_connection(db_lookup($tableName)))) {
        return false;
    }

    return db_prepare_raw($connection, $query);
}

/**
 * Привязка параметров к подготовленному запросу.
 *
 * @param object $statement
 * @param array $params
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
    if (!$statement) {
        return false;
    }

    // TODO: add log

    /** @var mysqli_stmt $statement */
    return mysqli_stmt_execute($statement);
}

/**
 * Получение объекта результата из подготовленного запроса.
 *
 * @param object $statement
 * @return bool|object
 */
function db_stmt_result($statement)
{
    if (!$statement) {
        return false;
    }

    /** @var mysqli_stmt $statement */
    return mysqli_stmt_get_result($statement);
}

/**
 * Запрос с привязкой параметров.
 *
 * @param string $tableName
 * @param string $query
 * @param array $params
 * @return bool|object
 */
function db_query($tableName, $query, array $params = [])
{
    $statement = db_prepare($tableName, $query);
    db_stmt_bind_params($statement, $params);
    db_stmt_execute($statement);

    return db_stmt_result($statement);
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

// TODO: add last inserted and affected rows
