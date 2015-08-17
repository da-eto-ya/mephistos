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
