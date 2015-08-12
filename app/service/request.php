<?php
/**
 * Функции для работы с запросами к серверу.
 */

/**
 * Получить параметры текущего запроса.
 *
 * @return array
 */
function request_get_current()
{
    // TODO: можно подумать, нужно ли здесь кеширование
    return [
        'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'method' => $_SERVER['REQUEST_METHOD'],
    ];
}
