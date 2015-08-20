<?php
/**
 * Различные функции, которые нам нужны, но не во всяком PHP они есть.
 */

if (!function_exists('intdiv')) {
    /**
     * Целочисленное деление.
     *
     * @param int $numerator
     * @param int $divisor
     * @return float
     */
    function intdiv($numerator, $divisor)
    {
        $numerator = (int) $numerator;
        $divisor = (int) $divisor;

        return ($numerator - $numerator % $divisor) / $divisor;
    }
}

/**
 * Возвращает массив, содержащий только указанные во втором параметре ключи.
 *
 * @param array $array входной хэш
 * @param array $keys значения ключей в плоском массиве
 * @return array массив, содержащий только указанные во втором параметре ключи
 */
function array_restrict($array, $keys)
{
    return array_intersect_key($array, array_fill_keys(array_values($keys), true));
}
