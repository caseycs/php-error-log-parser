<?php
class PhpLogParser53
{
    public function __construct(
        $logPath, 
        $temporaryDir,
        \Closure $deliverErrorsCallback,
        $pollDelay = 2,
        $keepLogDir = null
    ) {
        $this->logPath = $logPath;
        $this->temporaryDir = $temporaryDir;
        $this->pollDelay = $pollDelay * 1000000; //to milliseconds
        $this->deliverErrorsCallback = $deliverErrorsCallback;
        $this->keepLogDir = $keepLogDir;
    }

    public function start() {
        try {
            $this->processOldLogFiles();
            $this->loopOnNewFile();
        } catch (\Exception $e) {
            $this->log('ERROR ' . $e->getMessage());
        };
    }

    protected function processOldLogFiles()
    {
        self::log('process old log files');
        $this->prepareDir($this->temporaryDir);
        $it = new FilesystemIterator($this->temporaryDir);
        /** @var SplFileInfo $fileInfo */
        foreach ($it as $fileInfo) {
            $filepath = $fileInfo->getPathname();

            $content = $this->readFile($filepath);
            $errors = $this->parseLog($content);
            $this->sendErrors($errors);
            $this->unlinkProcessedLog($fileInfo->getFilename());
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
                    $newName = date('Y-m-d_H-i-s');
                    $newPath = $this->temporaryDir . '/' . $newName;

                    $this->rename($this->logPath, $this->temporaryDir, $newName);

                    $content = $this->readFile($newPath);
                    $errors = $this->parseLog($content);
                    unset($content);
                    $this->sendErrors($errors);
                    unset($errors);
                    $this->unlinkProcessedLog($newName);

                    self::log('memory usage ' . (memory_get_usage(true)/1024) . 'K');
                }
            }
            usleep($this->pollDelay);
        }
    }

    protected function prepareDir($dir)
    {
        if (is_dir($dir)) {
            if (!is_readable($dir)) {
                throw new \Exception('Directory does not exists or not readable:' . $dir);
            }
        } elseif (!mkdir($dir)) {
            throw new \Exception('failed to create directory:' . $dir);
        }
    }

    protected function readFile($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \Exception('file does not exists or is not readable:' . $filename);
        }
        self::log('read file ' . $filename);
        $result = file_get_contents($filename);
        if (false === $result) {
            throw new \Exception('file_get_contents failed:' . $filename);
        }
        return $result;
    }

    protected function unlinkProcessedLog($filename)
    {
        if ($this->keepLogDir) {
            $this->prepareDir($this->keepLogDir);
            $this->rename($this->temporaryDir . '/' . $filename, $this->keepLogDir, $filename);
        } else {
            if (@unlink($this->temporaryDir . '/' . $filename)) {
                self::log('file deleted successfully: ' . $filename);
            } else {
                throw new \Exception('unlink failed: ' . $filename);
            }
        }
    }

    protected function rename($pathFrom, $dirTo, $nameTo)
    {
        $this->prepareDir($dirTo);
        $to = $dirTo . '/' . $nameTo;

        if (@rename($pathFrom, $to)) {
            self::log("rename success: {$pathFrom} -> {$to}");
        } else {
            throw new \Exception("rename failed: {$pathFrom} -> {$to}");
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
        echo date('[Y-m-d H:i:s]') . ' ' . $message . PHP_EOL;
    }
}