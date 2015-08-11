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
            'orders_db' => ['order',],
        ],
        // соединение по умолчанию (не указанные в 'tables' таблицы ищутся в нём)
        'default' => 'main',
    ],
];
