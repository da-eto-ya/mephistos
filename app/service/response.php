<?php
/**
 * Ответы сервера.
 */

/**
 * Посылаем ответ клиенту.
 *
 * @param string $message
 * @param int    $code
 */
function response_send($message = '', $code = 200)
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
function response_json($result, $code = 200)
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
    response_send($message, 403);
}

/**
 * Ответ 404 (Not Found).
 *
 * @param string $message
 */
function response_not_found($message = 'Not Found')
{
    response_send($message, 404);
}

/**
 * Ответ 405 (Method Not Allowed).
 *
 * @param string $allow имя метода, который разрешается
 * @param string $message
 */
function response_change_method($allow = 'POST', $message = 'Method Not Allowed')
{
    response_send($message, 405);

    if ($allow) {
        header('Allow: ' . $allow);
    }
}

/**
 * Редирект на указанную страницу.
 *
 * @param string $url
 */
function response_redirect($url)
{
    header("Location: {$url}");
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
 * @param string $name
 * @param string $path
 * @param null   $domain
 * @return bool
 */
function response_remove_cookie($name, $path = '/', $domain = null)
{
    if (isset($_COOKIE[$name])) {
        unset($_COOKIE[$name]);
    }

    return setcookie($name, '', time() - 3600, $path, $domain);
}
