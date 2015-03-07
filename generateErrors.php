<?php
error_reporting(-1);
ini_set('display_errors', true);
ini_set('error_log', __DIR__ . '/php_error.log');
if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

//prepare dir
if (!file_exists('/tmp/php_error')) {
    mkdir('/tmp/php_error');
}

//generate errors
for ($i = 0; $i < 2; $i ++) {
    echo $a;
}
