<?php

function app_start()
{
    $path = $_SERVER['REQUEST_URI'];
    echo $path;
}
