<?php
/**
 * Основной компонент приложения.
 */

require_once __DIR__ . '/require.php';
require_services('db', 'log', 'router', 'request', 'response', 'template', 'security', 'auth');

/**
 * Запуск приложения.
 *
 * @param array $config конфигурация приложения
 */
function app_run(array $config)
{
    // конфигурируем модули и настройки
    $configurableModules = [
        'log' => 'log_config',
        'router' => 'router_config',
        'db' => 'db_config',
        'template' => 'template_config',
        'security' => 'security_config',
        'auth' => 'auth_config',
        // настройки
        'app_settings' => 'app_settings',
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

/**
 * Различные дополнительные настройки приложения
 *
 * @param array|null $config
 * @return mixed
 */
function app_settings(array $config = null)
{
    static $_config = [
        'ga' => false,
    ];

    if (null !== $config) {
        if (isset($config['ga'])) {
            $_config['ga'] = (bool) $config['ga'];
        }
    }

    return $_config;
}
