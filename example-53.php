<?php
error_reporting(-1);
ini_set('display_errors', true);

$log = '/tmp/php_error.log';
$logTemporaryDir = '/tmp/php_error';
$keepLogsDir = '/tmp/php_error_keep';

//callback function
$sendErrors = function(array $errors) {
    print_r($errors);
    return true;
};

//run
require 'lib-53.php';
$a = new PhpLogParser53($log, $logTemporaryDir, $sendErrors, 2, $keepLogsDir);
$a->start();