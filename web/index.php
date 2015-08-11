<?php

//require_once __DIR__ . '/../app/app.php';

//app_start(require_once __DIR__ . '/../app/config.php');

// testing OpenShift getenv
var_dump(getenv('OPENSHIFT_MYSQL_DB_HOST'));
