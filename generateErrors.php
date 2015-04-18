<?php
error_reporting(-1);
ini_set('display_errors', true);
ini_set('error_log', '/tmp/php_error.log');
if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

//prepare dir
if (!file_exists('/tmp/php_error')) {
    mkdir('/tmp/php_error');
}

//generate errors
echo $a;
throw new Exception;
