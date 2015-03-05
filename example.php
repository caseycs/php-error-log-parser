<?php
require 'lib.php';

$log = '/tmp/php_error.log';
$temporaryDir = '/tmp/php_error';

$sendErrors = function(array $errors) {
    print_r($errors);
    echo 'PIECE END' . PHP_EOL;
    return true;
};

$a = new PhpLogParser($log, $temporaryDir, 1);
$a->start($sendErrors);