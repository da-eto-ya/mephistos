<?php
/**
 * Денежные расчёты (конвертация, комиссия и т.п).
 */

/**
 * Конвертирует значение в долларах в центы.
 *
 * @param int|float|string $dollars
 * @return int
 */
function billing_format_dollars_as_cents($dollars)
{
    $dollars = str_replace(',', '.', $dollars);
    $parts = explode('.', $dollars);

    if (count($parts) > 2) {
        return 0;
    }

    $dollars = (int) $parts[0];
    $cents = isset($parts[1]) ? (int) $parts[1] : 0;

    if ($cents < 0 || $cents > 100) {
        return 0;
    }

    return $dollars * 100 + ($dollars >= 0 ? $cents : -$cents);
}

/**
 * Конвертирует значение в центах в доллары.
 *
 * @param int $cents
 * @return string
 */
function billing_format_cents_as_dollars($cents)
{
    $cents = (int) $cents;
    $div = intdiv($cents, 100);
    $mod = $cents % 100;

    return sprintf("%d.%02d", $div, ($cents >= 0 ? $mod : -$mod));
}
