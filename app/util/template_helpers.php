<?php
/**
 * Хелперы для шаблонизатора.
 */

require_once __DIR__ . '/../require.php';
require_services('billing');

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
