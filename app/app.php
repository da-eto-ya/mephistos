<?php
/**
 * Основной компонент приложения
 */

require_once __DIR__ . '/service/log.php';
require_once __DIR__ . '/service/db.php';

/**
 * Запуск приложения
 *
 * @param array $config конфигурация приложения
 */
function app_start(array $config)
{
    // тестим определение пути
    $request = [
        'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'method' => $_SERVER['REQUEST_METHOD'],
    ];

    // TODO: remove
    echo '<pre>';
    var_dump($request);
    echo '</pre>';

    // подключаем логирование, БД
    if (isset($config['log'])) {
        log_config($config['log']);
    }

    if (isset($config['db'])) {
        db_config($config['db']);
    }

    // тестим логирование
    // TODO: remove
    log_alert('how', ['are' => 'you']);
    log_critical('I am critical');

    // тестим БД
    // TODO: remove
    echo '<p>DB main' . mysqli_get_host_info(db_get_connection('main')) . '</p>';
    echo '<p>DB orders_db' . mysqli_get_host_info(db_get_connection('orders_db')) . '</p>';
}
