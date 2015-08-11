<?php
/**
 * Сервис логирования
 */

// Уровни логирования
const APP_LOG_EMERGENCY = 'emergency';
const APP_LOG_ALERT = 'alert';
const APP_LOG_CRITICAL = 'critical';
const APP_LOG_ERROR = 'error';
const APP_LOG_WARNING = 'warning';
const APP_LOG_NOTICE = 'notice';
const APP_LOG_INFO = 'info';
const APP_LOG_DEBUG = 'debug';

/**
 * Устанавливает или возвращает конфигурацию логирования.
 *
 * @param array|null $config массив для установки или null для возврата ранее сохранённого значения
 * @return array
 */
function log_config(array $config = null)
{
    static $_config = [
        'path' => null,
        'type' => 0,
    ];

    if (null !== $config) {
        if (isset($config['directory']) && is_string($config['directory']) && file_exists($config['directory'])) {
            $config['path'] = $config['directory'] . DIRECTORY_SEPARATOR . $config['filename'];
            $config['type'] = 3;
            $_config = $config;
        } else if (isset($config['type']) && 0 === $config['type']) {
            $config['path'] = null;
            $_config = $config;
        } else {
            error_log("Can't use " . json_encode($config) . " as log config", 0);
        }
    }

    return $_config;
}

/**
 * Логирование с произвольным уровнем.
 *
 * @param string $level
 * @param string $message
 * @param array $context
 */
function log_write($level, $message, array $context = [])
{
    $config = log_config();
    $msg = date('Y-m-d H:i:s') . "\t[{$level}]\t{$message}\t" . json_encode($context);
    $res = error_log($msg . ($config['type'] === 3 ? PHP_EOL : ""), $config['type'], $config['path']);

    if (!$res && 3 === $config['type']) {
        error_log($msg, 0, $config['path']);
    }
}

/**
 * Логировать сообщение: Система нестабильна.
 *
 * @param string $message
 * @param array $context
 *
 * @return null
 */
function log_emergency($message, array $context = [])
{
    log_write(APP_LOG_EMERGENCY, $message, $context);
}

/**
 * Логировать сообщение: Необходимо незамедлительное действие.
 *
 * Например: сайт лежит, база недоступна.
 *
 * @param string $message
 * @param array $context
 *
 * @return null
 */
function log_alert($message, array $context = [])
{
    log_write(APP_LOG_ALERT, $message, $context);
}

/**
 * Логировать сообщение: Критическое состояние.
 *
 * Например: компонент приложения недоступен, неожиданное исключение.
 *
 * @param string $message
 * @param array $context
 *
 * @return null
 */
function log_critical($message, array $context = [])
{
    log_write(APP_LOG_CRITICAL, $message, $context);
}

/**
 * Логировать сообщение: Не требует немедленного реагирования, но важно.
 *
 * @param string $message
 * @param array $context
 *
 * @return null
 */
function log_error($message, array $context = [])
{
    log_write(APP_LOG_ERROR, $message, $context);
}

/**
 * Логировать сообщение: Исключительные ситуации, не являющиеся ошибками.
 *
 * Например: Использование deprecated, неверное использование API.
 *
 * @param string $message
 * @param array $context
 *
 * @return null
 */
function log_warning($message, array $context = [])
{
    log_write(APP_LOG_WARNING, $message, $context);
}

/**
 * Логировать сообщение: Нормальная ситуация, просто небольшое замечание.
 *
 * @param string $message
 * @param array $context
 *
 * @return null
 */
function log_notice($message, array $context = [])
{
    log_write(APP_LOG_NOTICE, $message, $context);
}

/**
 * Логировать сообщение: Интересующие события.
 *
 * Например: залогинился пользователь, какие-нибудь SQL-логи.
 *
 * @param string $message
 * @param array $context
 *
 * @return null
 */
function log_info($message, array $context = [])
{
    log_write(APP_LOG_INFO, $message, $context);
}

/**
 * Логировать сообщение: Информация для дебага.
 *
 * @param string $message
 * @param array $context
 *
 * @return null
 */
function log_debug($message, array $context = [])
{
    log_write(APP_LOG_DEBUG, $message, $context);
}
