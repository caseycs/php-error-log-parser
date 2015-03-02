<?php
require 'lib.php';

$log = '/tmp/php_errors.log';
$temporaryDir = '/tmp/php_errors';

$sendErrors = function(array $errors) {
    print_r($errors);
    return true;
};

$phpLogParser = new PhpLogParser;
$phpLogParser->run($log, $temporaryDir, $sendErrors, 1, 1, 3);