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
    // TODO: remove all dumps
    echo '<pre>';

    // тестим определение пути
    $request = [
        'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'method' => $_SERVER['REQUEST_METHOD'],
    ];

    var_dump($request);

    // конфигурируем модули
    if (isset($config['log'])) {
        log_config($config['log']);
    }

    if (isset($config['db'])) {
        db_config($config['db']);
    }

    // тестим БД
    var_dump(db_fetch_all(db_query_raw_unsafe(db_connection(db_lookup('users')), 'SELECT * FROM `users`')));
    var_dump(db_fetch_all(db_query('orders', 'SELECT * FROM `orders`')));

    echo '</pre>';
}
