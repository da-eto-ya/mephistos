<?php
/**
 * Основной компонент приложения
 */

require_once __DIR__ . '/service/log.php';

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

    // подключаем логирование
    if (isset($config['log'])) {
        log_config($config['log']);
    }

    // тестим логирование
    log_alert('how', ['are' => 'you']);
    log_critical('I am critical');
}
