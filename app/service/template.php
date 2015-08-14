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
    static $__template_internal_stack = [];

    if (!is_file($filename)) {
        log_alert("Can't found file for render: " . $filename);

        return '';
    }

    // уберём всё лишнее из текущей области видимости перед extract
    array_push($__template_internal_stack, $filename, $params);
    unset($filename, $params);
    extract(array_pop($__template_internal_stack), EXTR_SKIP);
    ob_start();
    include array_pop($__template_internal_stack);
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
