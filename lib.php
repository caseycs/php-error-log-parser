<?php
class PhpLogParser
{
    public function __construct(
        $logPath, 
        $temporaryDir,
        $pollDelay = 1
    ) {
        $this->logPath = $logPath;
        $this->temporaryDir = $temporaryDir;
        $this->pollDelay = $pollDelay * 1000000; //to milliseconds
    }

    public function start(\Closure $deliverErrorsCallback) {
        $fileProcessorF = $this->fileProcessor();
        $logParserF = $this->logParser();

        $fileProcessor = $fileProcessorF();
        $logParser = $logParserF();

        $processFile = function($filename) use ($fileProcessor, $logParser, $deliverErrorsCallback) {
            $fileContents = $fileProcessor->send($filename);
            $errors = $logParser->send($fileContents);
            if (!$deliverErrorsCallback($errors)) {
                throw new \LogicException;
            }
            if (!unlink($filename)) {
                throw new \LogicException;
            }
        };

        echo "old files" . PHP_EOL;
        $processOldLogFiles = $this->processOldLogFiles();
        foreach ($processOldLogFiles($this->temporaryDir) as $file) {
            $processFile($file);
        }

        echo "new files" . PHP_EOL;
        $loopOnNewFile = $this->loopOnNewFile();
        foreach ($loopOnNewFile($this->logPath, $this->temporaryDir, $this->pollDelay) as $file) {
            $processFile($file);
        }
    }

    protected function fileProcessor()
    {
        return function() {
            $filename = yield;
            while (true) {
                if (!is_file($filename) || !is_readable($filename)) {
                    throw new \LogicException;
                }
                $filename2remove = $filename;
                $result = file_get_contents($filename);
                $filename = (yield $result);
            }
        };
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

    protected function logParser()
    {
        return function() {
            $content = yield;
            while (true) {
                $errors = preg_match_all('/\[(\d\d-\w{3}-\d{4}\s+\d\d:\d\d:\d\d\ [\w\/]*?)] (.+)/', $content, $matches);
                $errorsOutput = [];
                foreach ($matches[1] as $k => $time) {
                    $msg = $matches[2][$k];
                    $time = strtotime($time);
                    $errorsOutput[] = [$time, $msg];
                }
                $content = (yield $errorsOutput);
            }
        };
    }

}