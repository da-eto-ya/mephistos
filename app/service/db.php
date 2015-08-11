<?php
/**
 * Сервис работы с БД
 */

require_once __DIR__ . '/log.php';

function db_config(array $config = null)
{
    static $_config = [];

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
                $_config['lookup'] = [];

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

function db_get_connection($name)
{
    // TODO: cache connections
    $config = db_config();

    if (!isset($config['connections'][$name])) {
        log_critical("Can't find connection with name " . $name, $config);
        return false;
    }

    $conn = $config['connections'][$name];
    $link = mysqli_connect($conn['host'], $conn['username'], $conn['password'], $conn['database'], $conn['port']);

    if (!$link) {
        log_alert("DB connection error: (" . mysqli_connect_errno() . ") " . mysqli_connect_error());
        return null;
    }

    return $link;
}
