<?php
/**
 * Входной скрипт приложения.
 */

require_once __DIR__ . '/../app/require.php';
require_modules('app');

app_run(require_once __DIR__ . '/../app/config.php');
