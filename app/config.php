<?php
/**
 * Конфигурация приложения
 */

return [
    // конфигурация логирования
    'log' => [
        'directory' => getenv('OPENSHIFT_LOG_DIR') ?: realpath(__DIR__ . '/../log'),
        'filename' => 'mephistos.log',
    ],
];
