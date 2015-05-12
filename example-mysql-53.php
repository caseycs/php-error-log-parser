<?php
error_reporting(-1);
ini_set('display_errors', true);
$errorHandler = function () {
    $args = func_get_args();
    print 'ERROR' . PHP_EOL . print_r(array_splice($args, 1, 3), true);
    die;
};
set_error_handler($errorHandler, -1);

if (empty($argv[1]) || !is_file($argv[1])) {
    echo "Usage: " . basename(__FILE__) . " config.json" . PHP_EOL;
    die;
}

$configPath = $argv[1];
$settings = @json_decode(@file_get_contents($configPath));
if (!$settings) {
    echo "Config file invalid" . PHP_EOL;
    die;
}

//prepare insert sql
$sqlColumns = array('datetime = :datetime', 'message = :message');
foreach ($settings->extra_columns as $column => $value) {
    $sqlColumns[] = "{$column} = :{$column}";
}
$sqlColumns = join(', ', $sqlColumns);
$sql = "INSERT INTO {$settings->db->table} SET {$sqlColumns}";

//callback function
$sendErrors = function(array $errors) use ($settings, $sql) {
    //db connect
    $dbh = new PDO($settings->db->dsn, $settings->db->user, $settings->db->password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sth = $dbh->prepare($sql);
    foreach ($settings->extra_columns as $column => $value) {
        $sth->bindParam(':' . $column, $value);
    }

    $datetime = $message = null;
    $sth->bindParam(':datetime', $datetime);
    $sth->bindParam(':message', $message);

    foreach ($errors as $error) {
        list ($timestamp, $message) = $error;
        $datetime = date('Y-m-d H:i:s', $timestamp);
        $sth->execute();
    }

    //close db connection
    unset($sth, $dbh);

    return true;
};

//run
require 'lib-53.php';
$phpLogParser53 = new PhpLogParser53(
    $settings->log_file,
    $settings->tmp_dir,
    $sendErrors,
    2,
    isset($settings->archive_dir) ? $settings->archive_dir : false
);
$phpLogParser53->start();