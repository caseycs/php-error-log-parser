<?php
class PhpLogParser
{
    public function run(
        $logPath, 
        $temporaryDir,
        $pollDelay = 1
    ) {
        //cast
        $pollDelay = $pollDelay * 1000000; //to milliseconds

        $parseLogFile = $this->parseLogFile();

        echo "old files" . PHP_EOL;
        $processOldLogFiles = $this->processOldLogFiles();
        foreach ($processOldLogFiles($temporaryDir) as $file) {
            foreach ($parseLogFile($file) as $errorsArray) {
                yield $errorsArray;
                // unlink ($file);
            }
        }

        echo "new files" . PHP_EOL;
        $loopOnNewFile = $this->loopOnNewFile();
        foreach ($loopOnNewFile($logPath, $temporaryDir, $pollDelay) as $file) {
            foreach ($parseLogFile($file) as $errorsArray) {
                yield $errorsArray;
                // unlink ($file);
            }
        }
    }

    protected function processOldLogFiles()
    {
        return function($temporaryDir) {
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
                yield $result;
            }
        };
    }

    protected function loopOnNewFile()
    {
        return function($filepath, $temporaryDir, $usleep) {
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
                        echo (memory_get_usage(true)/1024) . 'K' . PHP_EOL;
                        yield $newPath;
                    }
                }
                usleep($usleep);
            }
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

            yield $errorsOutput;
        };
    }

}