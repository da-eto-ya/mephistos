<?php
/**
 * Простой шаблонизатор.
 */

require_once __DIR__ . '/../require.php';
require_services('log');

// считаем, что все, кто использует template, собираются использовать его хелперы
require_modules('util/template_helpers');

/**
 * Устанавливает или возвращает конфигурацию шаблонизатора.
 *
 * @param array|null $config массив для установки или null для возврата ранее сохранённого значения
 * @return array
 */
function template_config(array $config = null)
{
    static $_config = [
        'directory' => __DIR__ . '/../template',
        'postfix' => '.phtml',
    ];

    if (null !== $config) {
        if (isset($config['directory'])) {
            $dir = realpath($config['directory']);

            if ($dir && is_dir($dir)) {
                $_config['directory'] = $dir;
            }
        }

        if (isset($config['postfix'])) {
            $_config['postfix'] = (string) $config['postfix'];
        }
    }

    return $_config;
}

/**
 * Рендер произвольного файла.
 * Использовать с крайней осторожностью и при крайней необходимости.
 *
 * @param string $filename
 * @param array  $params
 * @return string результат рендера
 */
function template_render_raw($filename, array $params = [])
{
    if (!is_file($filename)) {
        log_alert("Can't found file for render: " . $filename);

        return '';
    }

    // если в $params есть ключ 'filename', то он затрёт имя файла; сохраним имя
    _template_internal_stack($filename);
    extract($params);
    ob_start();
    include _template_internal_stack();
    $result = (string) ob_get_clean();

    return $result;
}

/**
 * Рендер указанного шаблона.
 * Использовать с крайней осторожностью и при крайней необходимости.
 *
 * @param string $template путь до шаблона, исключая базовую директорию и расширение
 * @param array  $params
 * @return string результат рендера
 */
function template_render($template, array $params = [])
{
    $config = template_config();
    $filename = "{$config['directory']}/{$template}{$config['postfix']}";

    return template_render_raw($filename, $params);
}

/**
 * Внутреннее хранилище шаблонизатора.
 * Не использовать вне этого файла.
 *
 * @internal
 *
 * @param mixed ...$args сохраняется только первый аргумент
 * @return mixed null при установке значения или возвращает последний добавленный элемент и удаляет его из стека
 */
function _template_internal_stack(...$args)
{
    static $_values = [];

    if (count($args) > 0) {
        $_values[] = $args[0];

        return null;
    } else {
        return array_pop($_values);
    }
}
