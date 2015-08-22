<?php
/**
 * Ответы сервера.
 */

require_once __DIR__ . '/../require.php';
require_services('request');

// Некоторые стандартные коды
const APP_HTTP_OK = 200;

const APP_HTTP_MOVED_PERMANENTLY = 301;
const APP_HTTP_MOVED_TEMPORARILY = 302;
const APP_HTTP_SEE_OTHER = 303;
const APP_HTTP_NOT_MODIFIED = 304;

const APP_HTTP_BAD_REQUEST = 400;
const APP_HTTP_UNAUTHORIZED = 401;
const APP_HTTP_FORBIDDEN = 403;
const APP_HTTP_NOT_FOUND = 404;
const APP_HTTP_METHOD_NOT_ALLOWED = 405;

const APP_HTTP_INTERNAL_SERVER_ERROR = 500;
const APP_HTTP_INTERNAL_NOT_IMPLEMENTED = 501;
const APP_HTTP_INTERNAL_BAD_GATEWAY = 502;
const APP_HTTP_INTERNAL_SERVICE_UNAVAILABLE = 503;

// Специальный код для редиректа с помощью JS
const APP_HTTP_X_JSON_REDIRECT = 418; // I'm a Teapot

/**
 * Посылаем ответ клиенту.
 *
 * @param string $message
 * @param int    $code
 */
function response_send($message = '', $code = APP_HTTP_OK)
{
    http_response_code((int) $code);
    echo $message;
}

/**
 * Посылаем ответ в виде json.
 *
 * @param mixed $result сырой массив/объект для ответа
 * @param int   $code
 */
function response_json($result, $code = APP_HTTP_OK)
{
    http_response_code((int) $code);
    header('Content-Type: application/json');
    echo json_encode($result);
}

/**
 * Ответ 403 (Forbidden).
 *
 * @param string $message
 */
function response_forbidden($message = 'Forbidden')
{
    response_send($message, APP_HTTP_FORBIDDEN);
}

/**
 * Ответ 404 (Not Found).
 *
 * @param string $message
 */
function response_not_found($message = 'Not Found')
{
    response_send($message, APP_HTTP_NOT_FOUND);
}

/**
 * Ответ 405 (Method Not Allowed).
 *
 * @param string $allow имя метода, который разрешается
 * @param string $message
 */
function response_change_method($allow = 'POST', $message = 'Method Not Allowed')
{
    response_send($message, APP_HTTP_METHOD_NOT_ALLOWED);

    if ($allow) {
        header('Allow: ' . $allow);
    }
}

/**
 * Редирект на указанную страницу (Location: URL).
 *
 * @param string $url
 */
function response_location($url)
{
    header("Location: {$url}");
}

/**
 * Унифицированный редирект (работает с ajax).
 *
 * @param string    $url
 * @param null|bool $isAjax
 */
function response_redirect($url, $isAjax = null)
{
    if (null === $isAjax) {
        $isAjax = request_is_ajax();
    }

    if ($isAjax) {
        response_json($url, APP_HTTP_X_JSON_REDIRECT);
    } else {
        response_location($url);
    }
}

/**
 * Установка cookie.
 *
 * @param string    $name
 * @param string    $value
 * @param int       $expire
 * @param string    $path
 * @param null      $domain
 * @param null      $secure
 * @param bool|true $httpOnly
 * @return bool
 */
function response_write_cookie(
    $name,
    $value = '',
    $expire = 0,
    $path = '/',
    $domain = null,
    $secure = null,
    $httpOnly = null
) {
    $name = (string) $name;
    $value = (string) $value;

    return setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
}

/**
 * Удалить cookie с данным именем.
 *
 * @param string      $name
 * @param string      $path
 * @param string|null $domain
 * @return bool
 */
function response_remove_cookie($name, $path = '/', $domain = null)
{
    if (isset($_COOKIE[$name])) {
        unset($_COOKIE[$name]);
    }

    return setcookie($name, '', time() - 3600, $path, $domain);
}
