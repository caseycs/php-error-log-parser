<?php
error_reporting(-1);
ini_set('display_errors', true);
ini_set('error_log', '/tmp/php_errors.log');
if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

//prepare dir
mkdir('/tmp/php_errors');

//generate errors
for ($i = 0; $i < 100; $i ++) {
    echo $a;
}
