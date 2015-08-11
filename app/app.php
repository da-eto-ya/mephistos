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
    // тестим определение пути и конфиг
    $request = [
        'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'method' => $_SERVER['REQUEST_METHOD'],
    ];

    echo '<pre>';
    var_dump($request);
    var_dump($config);
    echo '</pre>';

    // подключаем логирование, БД
    if (isset($config['log'])) {
        log_config($config['log']);
    }

    if (isset($config['db'])) {
        db_config($config['db']);
    }

    // тестим логирование
    log_alert('how', ['are' => 'you']);
    log_critical('I am critical');

    // тестим БД
    $conn = db_get_connection('main');
    // TODO: remove mysqli functions
    echo mysqli_get_host_info($conn) . "\n";
    echo '<pre>';
    var_dump(db_config());
    echo '</pre>';
}
