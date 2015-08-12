<?php
/**
 * Основной компонент приложения
 */

require_once __DIR__ . '/require.php';
require_services('db', 'log', 'router', 'request', 'response');

/**
 * Запуск приложения
 *
 * @param array $config конфигурация приложения
 */
function app_start(array $config)
{
    // конфигурируем модули
    if (isset($config['log'])) {
        log_config($config['log']);
    }

    if (isset($config['router'])) {
        router_config($config['router']);
    }

    if (isset($config['db'])) {
        db_config($config['db']);
    }

    // резолвим и передаём запрос контроллеру
    $request = request_get_current();
    $route = router_resolve_route($request['path']);

    // не найден контроллер
    if (!$route) {
        response_not_found();
        return;
    }

    if ($route['include']) {
        require_once $route['include'];
    }

    // не найден подходящий метод для вызова
    if (!is_callable($route['callable'])) {
        response_not_found();
        return;
    }

    // дальше всё решает контроллер
    call_user_func($route['callable'], ...$route['params']);
}
