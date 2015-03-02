<?php
require 'lib.php';

$log = '/tmp/php_errors.log';
$temporaryDir = '/tmp/php_errors';

$phpLogParser = new PhpLogParser;
foreach ($phpLogParser->run($log, $temporaryDir, 1) as $errors) {
    print_r($errors);
}

// protected function sendErrors(\Closure $sendErrorsCallback, $retriesCount, $retryDelay)
// {
//     return function(array $errors) use ($sendErrorsCallback, $retriesCount, $retryDelay) {
//         echo 'deliver' . PHP_EOL;
//         if (array() === $errors) {
//             throw new \LogicException;
//         }

//         do {
//             if ($sendErrorsCallback($errors)) {
//                 echo "delivered " . count($errors) . ' errors' . PHP_EOL;
//                 return true;
//             }
//             echo "delivered failed, usleep 1 sec" . PHP_EOL;
//             usleep($retryDelay);
//             $retriesCount --;
//         } while ($retriesCount > 0);

//         throw new \Exception("deliver failed after {$sendRetries} replies");
//     };
// }