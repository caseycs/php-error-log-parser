<?php
error_reporting(-1);
ini_set('display_errors', true);

//settings - library-related
$log = getenv('LOG') ?: '/tmp/php_error.log';
$logTemporaryDir = getenv('LOG_TEMPORARY_DIR') ?: '/tmp/php_error';
$logKeepDir = getenv('LOG_KEEP_DIR') ?: '/tmp/php_error_keep';

//settings - custom-code related
$host = getenv('MYSQL_HOST') ?: 'localhost';
$port = getenv('MYSQL_PORT') ?: 3306;
$user = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';
$database = getenv('MYSQL_DATABASE') ?: 'test';
$table = getenv('MYSQL_TABLE') ?: 'php_error';
$extraColumns = array();
if (getenv('EXTRA_COLUMNS')) {
    foreach(explode(',', getenv('EXTRA_COLUMNS')) as $tmp) {
        list ($k, $v) = explode('=', $tmp);
        $extraColumns[trim($k)] = trim($v);
    }
}

//db connect
$dsn = "mysql:dbname={$database};host={$host};port={$port}";
$dbh = new PDO($dsn, $user, $password);
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//prepare insert query
$sqlColumns = array('datetime = :datetime', 'message = :message');
foreach (array_keys($extraColumns) as $column) {
    $sqlColumns[] = "{$column} = :{$column}";
}
$sql = "INSERT INTO {$table} SET " . join(', ', $sqlColumns);
$sth = $dbh->prepare($sql);
foreach (array_keys($extraColumns) as $column) {
    $sth->bindParam(':' . $column, $extraColumns[$column]);
}

$datetime = $message = null;
$sth->bindParam(':datetime', $datetime);
$sth->bindParam(':message', $message);

//callback function
$sendErrors = function(array $errors) use ($sth, &$datetime, &$message) {
    foreach ($errors as $error) {
        list ($timestamp, $message) = $error;
        $datetime = date('Y-m-d H:i:s', $timestamp);
        $sth->execute();
    }
    return true;
};

//run
require 'lib-53.php';
$phpLogParser53 = new PhpLogParser53($log, $logTemporaryDir, $sendErrors, 2, $logKeepDir);
$phpLogParser53->start();