<?php
/**
 * Функции для работы с запросами к серверу.
 */

require_once __DIR__ . '/../require.php';

// считаем, что все, кто использует request, собираются использовать его хелперы
require_utils('request_helpers');

/**
 * Получить параметры текущего запроса.
 *
 * @return array
 */
function request_get_current()
{
    static $_request = null;

    if (null === $_request) {
        $_request = [
            'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            'method' => $_SERVER['REQUEST_METHOD'],
        ];
    }

    return $_request;
}

/**
 * Проверяет, что запрос имеет метод POST.
 *
 * @param array|null $request массив запроса или null (текущий запрос)
 * @return bool
 */
function request_is_post(array $request = null)
{
    return request_has_method('POST', $request);
}

/**
 * Проверяет, что запрос имеет метод GET.
 *
 * @param array|null $request массив запроса или null (текущий запрос)
 * @return bool
 */
function request_is_get(array $request = null)
{
    return request_has_method('GET', $request);
}

/**
 * Проверяет, что запрос имеет указанный метод.
 *
 * @param string     $method имя HTTP метода
 * @param array|null $request массив запроса или null (текущий запрос)
 * @return bool
 */
function request_has_method($method, array $request = null)
{
    if (null === $request) {
        $request = request_get_current();
    }

    return isset($request['method']) && $method === $request['method'];
}

/**
 * Читает cookie.
 *
 * @param string $name
 * @return string|bool
 */
function request_read_cookie($name)
{
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : false;
}
