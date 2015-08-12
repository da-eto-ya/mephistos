<?php
/**
 * Простейший роутинг приложения.
 */

/**
 * Устанавливает или возвращает конфигурацию роутера.
 *
 * @param array|null $config массив для установки или null для возврата ранее сохранённого значения
 * @return array
 */
function router_config(array $config = null)
{
    static $_config = [
        'routes' => [],
    ];
    static $dir = null;
    static $dirLength = 0;
    static $cachedControllers = [];

    if (null !== $config) {
        if (!empty($config['routes'])) {
            if (null === $dir) {
                $dir = realpath(__DIR__ . '/..');
                $dirLength = strlen($dir);
            }

            foreach ($config['routes'] as $prefix => $controller) {
                if (!isset($cachedControllers[$controller])) {
                    $filename = realpath(__DIR__ . "/../controller/{$controller}.php");

                    if ($filename && is_file($filename) && $dir === substr($filename, 0, $dirLength)) {
                        $cachedControllers[$controller] = [
                            'controller' => $controller,
                            'include' => $filename,
                        ];
                    } else {
                        $cachedControllers[$controller] = false;
                    }
                }

                if ($cachedControllers[$controller]) {
                    $_config['routes'][$prefix] = $cachedControllers[$controller];
                }
            }
        }
    }

    return $_config;
}

/**
 * Определяет параметры контроллера (роут) по переданному пути.
 *
 * @param string $path
 * @return array|bool false в случае неудачи
 */
function router_resolve_route($path)
{
    $path = (string) $path;
    $config = router_config();
    $prefix = '';
    $action = 'index';
    $params = [];

    $segments = array_map('urldecode', explode('/', ltrim($path, '/')));
    $segmentsCount = count($segments);

    if ($segmentsCount > 0) {
        $prefix = $segments[0];
    }

    // префикс и контроллер должны быть объявлены в конфигурации
    if (!isset($config['routes'][$prefix]['controller'])) {
        return false;
    }

    $route = $config['routes'][$prefix];

    if ($segmentsCount > 1) {
        $action = $segments[1];
    }

    // экшен должен быть похож на часть имени функции
    if (!preg_match('/^\w+$/', $action)) {
        return false;
    }

    if ($segmentsCount >= 2) {
        $params = array_slice($segments, 2);
    }

    return [
        'callable' => "controller_{$route['controller']}_{$action}",
        'params' => $params,
        'controller' => $route['controller'],
        'action' => $action,
        'include' => $route['include'],
    ];
}

/**
 * Формирует строку URL для заданного роута.
 *
 * @param string $controller
 * @param string $action
 * @param array $params
 * @return string
 */
function router_get_path($controller, $action = 'index', $params = [])
{
    $params = (array) $params;

    if (!$action) {
        $action = 'index';
    }

    if ('index' == $action && !$params) {
        $segments = [$controller];
    } else {
        $segments = array_merge([$controller, $action], $params);
    }

    $segments = array_map('urlencode', array_map('strval', $segments));

    return '/' . join('/', $segments);
}
