<?php
/**
 * Конфигурация приложения
 */

return [
    // конфигурация логирования
    'log' => [
        'directory' => getenv('OPENSHIFT_LOG_DIR') ?: __DIR__ . '/../log',
        'filename' => 'mephistos.log',
    ],
    // конфигурация роутинга
    'router' => [
        // роуты в виде 'префикс' => 'контроллер'
        'routes' => [
            'login',
            'logout',
            'orders',
            '' => 'main',
        ],
    ],
    // конфигурация БД
    'db' => [
        // доступные соединения
        'connections' => [
            // главное соединение, содержит основные таблицы
            'main' => [
                'host' => getenv('OPENSHIFT_MYSQL_DB_HOST') ?: '127.0.0.1',
                'port' => getenv('OPENSHIFT_MYSQL_DB_PORT') ?: '3306',
                'username' => getenv('OPENSHIFT_MYSQL_DB_USERNAME') ?: 'root',
                'password' => getenv('OPENSHIFT_MYSQL_DB_PASSWORD') ?: '',
                'database' => 'mephistos_main',
            ],
            // соединение уровня модуля
            'orders_db' => [
                'host' => getenv('OPENSHIFT_MYSQL_DB_HOST') ?: '127.0.0.1',
                'port' => getenv('OPENSHIFT_MYSQL_DB_PORT') ?: '3306',
                'username' => getenv('OPENSHIFT_MYSQL_DB_USERNAME') ?: 'root',
                'password' => getenv('OPENSHIFT_MYSQL_DB_PASSWORD') ?: '',
                'database' => 'mephistos_orders',
            ],
        ],
        // массив принадлежности таблиц в виде 'имя соединения' => ['список', 'таблиц']
        'tables' => [
            'orders_db' => ['orders',],
        ],
        // соединение по умолчанию (не указанные в 'tables' таблицы ищутся в нём)
        'default' => 'main',
    ],
    // конфигурация шаблонизатора
    'template' => [
        'directory' => __DIR__ . '/template',
        'postfix' => '.phtml',
    ],
    // компонент безопасности
    'security' => [
        'cost' => 11,
    ],
    // TODO: возможно, стоит перенести часть конфигов auth в security или слить их
    // компонент аутентификации
    'auth' => [
        'secret_key' => getenv('OPENSHIFT_SECRET_TOKEN') ?: hash('sha256', 'oh my secret key!'),
        'domain' => getenv('OPENSHIFT_APP_NAME') ? '.mephistos.da-eto.info' : '.mephistos.loc',
        'expire' => 86400, // 1d
    ],
];
