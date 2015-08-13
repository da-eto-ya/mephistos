<?php
/**
 * Хелперы для шаблонизатора.
 */

/**
 * Экранируем выходные значения.
 *
 * @param string $str
 * @return string
 */
function e($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
