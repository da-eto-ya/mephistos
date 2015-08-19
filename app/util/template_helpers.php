<?php
/**
 * Хелперы для шаблонизатора.
 */

require_once __DIR__ . '/../require.php';
require_services('billing', 'auth');

/**
 * Экранируем выходные значения.
 *
 * @param string $str
 * @return string
 */
function _e($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Форматирует целочисленное значение центов в строку долларов с плавающей точкой.
 * Использовать для перевода внутреннего представления денег в человекопонятное.
 *
 * @param int $number
 * @return string
 */
function _money($number)
{
    return billing_format_cents_as_dollars($number);
}

/**
 * CSRF-токен для действия.
 *
 * @param array $params
 * @return string
 */
function _csrf($params = [])
{
    return (string) auth_get_csrf((array) $params);
}

/**
 * input[type=hidden] с CSRF-токеном для заданного действия.
 *
 * @param array  $params параметры действия
 * @param string $name input[name]
 * @return string
 */
function _csrf_hidden($params = [], $name = '_csrf')
{
    return '<input type="hidden" name="' . _e($name) . '" value="' . _e(_csrf($params)) . '">';
}
