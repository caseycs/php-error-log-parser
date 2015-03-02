<?php
$log = '/tmp/php_errors.log';
$temporaryDir = '/tmp/php_errors';

$moveNewLogfile = function($filepath, $temporaryDir, $usleep, \Closure $callback) {
    echo 'move log file' . PHP_EOL;
    if (!is_dir($temporaryDir) || !is_writeable($temporaryDir)) {
        throw new \LogicException;
    }

    while (true) {
        if (is_file($filepath)) {
            if (!is_readable($filepath)) {
                throw new \LogicException;
            }

            if (filesize($filepath)) {
                $newPath = $temporaryDir . '/' . time();
                if (!rename($filepath, $newPath)) {
                    throw new \LogicException;
                }
                $callback($newPath);
                echo (memory_get_usage(true)/1024) . 'K' . PHP_EOL;
            }
        }
        usleep($usleep);
    }
};

$oldLogFiles = function($temporaryDir, \Closure $callback) {
    echo 'old log file' . PHP_EOL;
    if (!is_dir($temporaryDir) || !is_readable($temporaryDir)) {
        throw new \LogicException;
    }

    $it = new FilesystemIterator($temporaryDir);
    foreach ($it as $fileinfo) {
        $result = $fileinfo->getPathname();
        if (!is_file($result) || !is_readable($result)) {
            throw new \LogicException;
        }
        $callback($result);
    }
};

$sendErrorsCallback = function(array $errors) {
    print_r($errors);
    return true;
};

$deliverLogFile = function($filepath) use ($sendErrorsCallback) {
    echo 'deliver' . PHP_EOL;
    if (!is_file($filepath) || !is_readable($filepath)) {
        throw new \LogicException;
    }

    $content = file_get_contents($filepath);
    $errors = preg_match_all('/\[(\d\d-\w{3}-\d{4}\s+\d\d:\d\d:\d\d\ [\w\/]*?)] (.+)/', $content, $matches);
    $errorsOutput = [];
    foreach ($matches[1] as $k => $time) {
        $msg = $matches[2][$k];
        $timeObj = strtotime($time);
        $errorsOutput[] = [$timeObj, $msg];
        // print_R([$timeObj, $msg]);
    }

    $sendRetries = 3;
    do {
        if ($sendErrorsCallback($errorsOutput)) {
            echo "delivered " . $filepath . ' ' . filesize($filepath) . 'b' . PHP_EOL;
            unlink($filepath);
            return;
        }
        echo "delivered failed, usleep 1 sec" . PHP_EOL;
        sleep(1);
        $sendRetries --;
    } while ($sendRetries > 0);

    throw new \Exception("deliver failed after {$sendRetries} replies");
};

// echo "current files" . PHP_EOL;
$oldLogFiles($temporaryDir, $deliverLogFile);

// echo "new files" . PHP_EOL;
$moveNewLogfile($log, $temporaryDir, 1000000, $deliverLogFile);