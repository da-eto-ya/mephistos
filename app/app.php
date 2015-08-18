<?php
/**
 * Основной компонент приложения.
 */

require_once __DIR__ . '/require.php';
require_services('db', 'log', 'router', 'request', 'response', 'template', 'security', 'auth', 'billing');

/**
 * Запуск приложения.
 *
 * @param array $config конфигурация приложения
 */
function app_run(array $config)
{
    // конфигурируем модули
    $configurableModules = [
        'log' => 'log_config',
        'router' => 'router_config',
        'db' => 'db_config',
        'template' => 'template_config',
        'security' => 'security_config',
        'auth' => 'auth_config',
        'billing' => 'billing_config',
    ];

    foreach ($configurableModules as $key => $callable) {
        if (isset($config[$key])) {
            call_user_func($callable, $config[$key]);
        }
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
