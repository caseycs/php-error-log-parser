<?php
class PhpLogParser
{
    public function run(
        $logPath, 
        $temporaryDir,
        \Closure $sendErrorsCallback,
        $pollDelay = 1,
        $retryDelay = 1,
        $numRetries = 3
    ) {
        //cast
        $pollDelay = $pollDelay * 1000000; //to milliseconds
        $retryDelay = $retryDelay * 1000000; //to milliseconds

        //bricks
        $parseLogFile = $this->parseLogFile();
        $sendErrors = $this->sendErrors($sendErrorsCallback, $numRetries, $retryDelay);

        //combine
        $deliverLogFile = $this->deliveryLogFile($parseLogFile, $sendErrors);

        // echo "current files" . PHP_EOL;
        $func = $this->processOldLogFiles();
        $func($temporaryDir, $deliverLogFile);

        // echo "new files" . PHP_EOL;
        $func = $this->loopOnNewFile();
        $func($logPath, $temporaryDir, $pollDelay, $deliverLogFile);
    }

    protected function processOldLogFiles()
    {
        return function($temporaryDir, \Closure $callback) {
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
    }

    protected function loopOnNewFile()
    {
        return function($filepath, $temporaryDir, $usleep, \Closure $callback) {
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
    }

    protected function deliveryLogFile(\Closure $parseLogFile, \Closure $sendErrors)
    {
        return function($logFile) use ($parseLogFile, $sendErrors) {
            $errors = $parseLogFile($logFile);
            $sendErrors($errors);
            unlink ($logFile);
        };
    }

    protected function parseLogFile()
    {
        return function($filepath) {
            echo 'parse' . PHP_EOL;
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

            return $errorsOutput;
        };
    }

    protected function sendErrors(\Closure $sendErrorsCallback, $retriesCount, $retryDelay)
    {
        return function(array $errors) use ($sendErrorsCallback, $retriesCount, $retryDelay) {
            echo 'deliver' . PHP_EOL;
            if (array() === $errors) {
                throw new \LogicException;
            }

            do {
                if ($sendErrorsCallback($errors)) {
                    echo "delivered " . count($errors) . ' errors' . PHP_EOL;
                    return true;
                }
                echo "delivered failed, usleep 1 sec" . PHP_EOL;
                usleep($retryDelay);
                $retriesCount --;
            } while ($retriesCount > 0);

            throw new \Exception("deliver failed after {$sendRetries} replies");
        };
    }
}