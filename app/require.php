<?php
/**
 * Функции для упрощённого подключения файлов.
 */

/**
 * Хелпер подключения файлов.
 *
 * @param array $modules
 */
function require_raw(...$modules)
{
    static $required = [];
    static $dir = null;
    static $dirLen = 0;

    if (null === $dir) {
        $dir = realpath(__DIR__);
        $dirLen = strlen($dir);
    }

    foreach ($modules as $module) {
        if (isset($required[$module])) {
            continue;
        }

        $filename = $dir . '/' . $module . '.php';

        if (file_exists($filename) && substr($filename, 0, $dirLen) === $dir) {
            require_once $filename;
            $required[$module] = true;
        }
    }
}

/**
 * Подключение файлов с общим префиксом (например, директория).
 *
 * @param string $prefix
 * @param array $modules
 */
function require_prefixed($prefix, ...$modules)
{
    $prefix = (string) $prefix;
    $prefixed = [];

    foreach ($modules as $module) {
        $prefixed[] = $prefix . $module;
    }

    require_raw(...$prefixed);
}

/**
 * Подключение сервисов.
 *
 * @param array $services
 */
function require_services(...$services)
{
    require_prefixed('service/', ...$services);
}
