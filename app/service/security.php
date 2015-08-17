<?php
/**
 * Сервис безопасности (пароли).
 */

/**
 * Устанавливает или возвращает конфигурацию компонента безопасности.
 *
 * @param array|null $config массив для установки или null для возврата ранее сохранённого значения
 * @return array
 */
function security_config(array $config = null)
{
    static $_config = [
        'algo' => PASSWORD_DEFAULT,
        'cost' => 12,
    ];

    if (null !== $config) {
        if (isset($config['algo']) && in_array($config['algo'], [PASSWORD_DEFAULT, PASSWORD_BCRYPT])) {
            $_config['algo'] = $config['algo'];
        }

        if (isset($config['cost']) && is_int($config['cost']) && 4 <= $config['cost'] && $config['cost'] <= 31) {
            $_config['cost'] = $config['cost'];
        }
    }

    return $_config;
}

/**
 * Получить хэш пароля.
 *
 * @param string $password
 * @return bool|string false в случае ошибки, хэш в случае удачного завершения
 */
function security_password_hash($password)
{
    $config = security_config();

    return password_hash($password, $config['algo'], ['cost' => $config['cost']]);
}

/**
 * Проверка соответствия пароля хэшу.
 *
 * @param string $password проверяемый пароль
 * @param string $hash сохранённый хэш истинного пароля
 * @return bool
 */
function security_password_verify($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Проверка, что хэш пароля необходимо пересчитать.
 *
 * @param string $hash
 * @return string
 */
function security_password_needs_rehash($hash)
{
    $config = security_config();

    return password_needs_rehash($hash, $config['algo'], ['cost' => $config['cost']]);
}
