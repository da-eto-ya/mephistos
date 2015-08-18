<?php
/**
 * Денежные расчёты (конвертация, комиссия и т.п).
 */

require_once __DIR__ . '/../require.php';
require_repos('users', 'orders');

/**
 * Устанавливает или возвращает настройки биллинга.
 *
 * @param array|null $config массив для установки или null для возврата ранее сохранённого значения
 * @return array
 */
function billing_config(array $config = null)
{
    static $_config = [
        'commission' => 13,
    ];

    if (null !== $config) {
        if (isset($config['commission']) && is_int($config['commission'])
            && 0 <= $config['commission'] && $config['commission'] <= 100
        ) {
            $_config['commission'] = $config['commission'];
        }
    }

    return $_config;
}

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

/**
 * Расчёт вознаграждения пользователя и системы.
 *
 * @param int $value
 * @param int $commission
 * @return array [сумма пользователя, награда системы]
 */
function billing_split_revenue($value, $commission = null)
{
    $value = (int) $value;

    if (!$value) {
        return [0, 0];
    }

    if (null === $commission) {
        $commission = billing_config()['commission'];
    } else {
        $commission = (int) $commission;
    }

    if ($commission <= 0) {
        return [$value, 0];
    }

    if ($commission >= 100) {
        return [0, $value];
    }

    // TODO: для больших сумм можно использовать bcmath или gmp, чтобы не произошло переполнения при умножении
    $user = intdiv($value * (100 - $commission), 100);
    $system = $value - $user;

    return [$user, $system];
}

/**
 * Исполнить заказ от имени пользователя и начислить деньги.
 *
 * @param int       $id id заказа
 * @param array|int $executor исполнитель
 * @return bool
 */
function billing_order_execute($id, $executor)
{
    $order = repo_orders_get_one_new($id);

    if (!$order) {
        return false;
    }

    if (is_int($executor)) {
        $executor = repo_users_get_executor_by_id($executor);
    }

    if (!$executor || !isset($executor['id'])) {
        return false;
    }

    $customer = repo_users_get_customer_by_id($order['customer_id']);

    if (!$customer) {
        return false;
    }

    $executed = repo_orders_execute($order['id'], $executor['id']);

    if (!$executed) {
        return false;
    }

    $revenues = billing_split_revenue($order['price']);
    $userProfit = $revenues[0];
    $customerProfit = -$order['price'];

    $executorPaid = repo_users_add_balance($executor['id'], $userProfit);
    $customerPaid = repo_users_add_balance($customer['id'], $customerProfit);
    // TODO: по-хорошему, нужно где-то ещё иметь счёт системы, на который класть $systemProfit

    if (!$executorPaid || !$customerPaid) {
        /*$orderCanceled = */repo_orders_cancel($order['id']);
        // TODO: log if false

        if ($executorPaid) {
            /*$executorCanceled = */repo_users_sub_balance($executor['id'], $userProfit);
            // TODO: log if false
        }

        if ($customerPaid) {
            /*$customerCanceled = */repo_users_sub_balance($customer['id'], $customerProfit);
            // TODO: log if false
        }

        return false;
    }

    return true;
}
