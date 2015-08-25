<?php
/**
 * Репозиторий работы с настройками сайта, хранящимися в БД.
 */

require_once __DIR__ . '/../require.php';
require_services('db', 'validate');

/**
 * Получить значение настройки.
 *
 * @param string $name
 * @return array|null
 */
function repo_settings_get($name)
{
    if (!$name) {
        return null;
    }

    $row = db_get_one_unsafe('settings', 'name', $name);

    if (!$row) {
        return null;
    }

    return $row['value'];
}

/**
 * Установить значение настройки.
 *
 * @param string $name
 * @param mixed  $value
 * @return bool|int
 */
function repo_settings_set($name, $value)
{
    if (!$name) {
        return false;
    }

    $rows = db_exec(
        'settings',
        'INSERT INTO `settings` (`name`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?',
        [$name, $value, $value]
    );

    // INSERT INTO ... ON DUPLICATE KEY UPDATE ... возвращает:
    //   * 0, если не было реального обновления (значение совпадает с тем, что в базе)
    //   * 1, если была вставлена новая строка
    //   * 2, если было обновление
    //   * -1, если запрос прошёл с ошибкой

    return ($rows >= 0);
}
