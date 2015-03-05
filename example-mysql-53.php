<?php
error_reporting(-1);
ini_set('display_errors', true);

//get env
$log = getenv('LOG') ?: '/tmp/php_error.log';
$logTemporaryDir = getenv('LOG_TEMPORARY_DIR') ?: '/tmp/php_error';
$host = getenv('MYSQL_HOST') ?: 'localhost';
$port = getenv('MYSQL_PORT') ?: 3306;
$user = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';
$database = getenv('MYSQL_DATABASE') ?: 'test';
$table = getenv('MYSQL_TABLE') ?: 'php_error';
$extra = getenv('EXTRA_COLUMNS'); //for example host=host1,env=cli
                                //assumes that host and env columns are presented in mysql table

//parse env
$extraFields = array();
if ($extra) {
    foreach(explode(',', $extra) as $tmp) {
        list ($k, $v) = explode('=', $tmp);
        $extraFields[trim($k)] = trim($v);
    }
}

//db connect
$dsn = "mysql:dbname={$database};host={$host};port={$port}";
$dbh = new PDO($dsn, $user, $password);
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//prepare insert query
$sqlColumns = array('datetime = :datetime', 'message = :message');
foreach (array_keys($extraFields) as $column) {
    $sqlColumns[] = "{$column} = :{$column}";
}
$sql = "INSERT INTO {$table} SET " . join(', ', $sqlColumns);
$sth = $dbh->prepare($sql);
foreach ($extraFields as $column => $value) {
    $sth->bindParam(':' . $column, $value);
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
$a = new PhpLogParser53($log, $logTemporaryDir, $sendErrors, 1);
$a->start();