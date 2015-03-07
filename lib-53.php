<?php
class PhpLogParser53
{
    public function __construct(
        $logPath, 
        $temporaryDir,
        \Closure $deliverErrorsCallback,
        $pollDelay = 1
    ) {
        $this->logPath = $logPath;
        $this->temporaryDir = $temporaryDir;
        $this->pollDelay = $pollDelay * 1000000; //to milliseconds
        $this->deliverErrorsCallback = $deliverErrorsCallback;
    }

    public function start() {
        $this->checkTemporaryDir();
        $this->processOldLogFiles();
        $this->loopOnNewFile();
    }

    protected function checkTemporaryDir()
    {
        if (is_dir($this->temporaryDir)) {
            if (!is_readable($this->temporaryDir)) {
                throw new \Exception('temporary dir does not exists or not readable:' . $this->temporaryDir);
            }
        } elseif (!mkdir($this->temporaryDir)) {
            throw new \Exception('failed to create temporary dir:' . $this->temporaryDir);
        }
    }

    protected function processOldLogFiles()
    {
        self::log('process old log files');
        $it = new FilesystemIterator($this->temporaryDir);
        foreach ($it as $fileinfo) {
            $result = $fileinfo->getPathname();
            if (!is_file($result) || !is_readable($result)) {
                throw new \Exception('file does not exists or is not readable:' . $result);
            }

            $content = $this->readFile($result);
            $errors = $this->parseLog($content);
            $this->sendErrors($errors);
            $this->unlink($result);
        }
    }

    protected function loopOnNewFile()
    {
        self::log('loop on new file');
        while (true) {
            if (is_file($this->logPath)) {
                if (!is_readable($this->logPath)) {
                    throw new \Exception('log file is not readable:' . $this->logPath);
                }

                if (filesize($this->logPath)) {
                    $newPath = $this->temporaryDir . '/' . time() . '_' . uniqid();
                    if (rename($this->logPath, $newPath)) {
                        self::log("rename success: {$this->logPath} -> {$newPath}");
                } else { 
                        throw new \Exception("rename failed: {$this->logPath} -> {$newPath}");
                    }

                    $content = $this->readFile($newPath);
                    $errors = $this->parseLog($content);
                    unset($content);
                    $this->sendErrors($errors);
                    unset($errors);
                    $this->unlink($newPath);

                    self::log('memory usage ' . (memory_get_usage(true)/1024) . 'K');
                }
            }
            usleep($this->pollDelay);
        }
    }

    protected function readFile($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \Exception('file does not exists or is not readable:' . $filename);
        }
        self::log('read file ' . $filename);
        $result = file_get_contents($filename);
        return $result;
    }

    protected function unlink($filename)
    {
        if (unlink($filename)) {
            self::log('file deleted successfully: ' . $filename);
        } else {
            throw new \Exception('remove error log file failed: ' . $filename);
        }
    }

    protected function parseLog($content)
    {
        $pattern = '/\[(\d\d-\w{3}-\d{4}\s+\d\d:\d\d:\d\d\ [\w\/]*?)] /';
        $flags = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY;
        $matches = preg_split($pattern, $content, -1, $flags);

        $errorsOutput = array();
        while (($msg = array_pop($matches)) && ($time = array_pop($matches))) {
            $time = strtotime($time);
            $errorsOutput[] = array($time, $msg);
        }
        self::log('log parsed, errors found: ' . count($errorsOutput));
        return $errorsOutput;
    }

    protected function sendErrors(array $errors)
    {
        $callback = $this->deliverErrorsCallback;
        if ($callback($errors)) {
            self::log('errors inserted successfully');
        } else {
            throw new \Exception('deliver errors failed');
        }
    }

    public static function log($message)
    {
        echo date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
    }

}