<?php
/**
 * Ответы сервера.
 */

/**
 * Посылаем ответ клиенту.
 *
 * @param string $message
 * @param int $code
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
 * @param int $code
 */
function response_json($result, $code = 200)
{
    http_response_code((int) $code);
    header('Content-Type: application/json');
    echo json_encode($result);
}

/**
 * Ответ 404.
 *
 * @param string $message
 */
function response_not_found($message = 'Not found')
{
    response_send($message, 404);
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
