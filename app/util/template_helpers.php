<?php
/**
 * Хелперы для шаблонизатора.
 */

require_once __DIR__ . '/../require.php';
require_modules('functions');

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
    $number = (int) $number;
    $div = intdiv($number, 100);
    $mod = $number % 100;

    return sprintf("%d.%02d", $div, ($number >= 0 ? $mod : -$mod));
}
