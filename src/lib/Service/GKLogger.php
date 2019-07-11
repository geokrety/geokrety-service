<?php

namespace Service;

use \Psr\Log\LogLevel;
use \Azurre\Component\Logger;
use \Azurre\Component\Logger\Handler\File;

/**
 * GKLogger rely on 'azurre/php-simple-logger'
 */
class GKLogger extends ConfigurableService {
    const CONFIG_LOG_FILE = 'LOG_FILE';
    const CONFIG_LOG_LEVEL = 'LOG_LEVEL';

    protected $logFile = 'php://stdout';
    protected $logLevel = 'DEBUG';

    private $link = null;

    public function __construct($channel = null) {
        $this->initConfig([], self::CONFIG_LOG_FILE, "logFile");
        $this->initConfig([], self::CONFIG_LOG_LEVEL, "logLevel");
        // DEBUG // echo "logFile:$this->logFile, logLevel:$this->logLevel\n";

        $this->link = new Logger($channel);
        $this->link->setHandler(new File($this->logFile));

        // by default log all
        if ($this->logLevel == "WARNING" || $this->logLevel == "WARN") {
            $this->link->setLogLevel(LogLevel::WARNING);
        } else if ($this->logLevel == "ERROR") {
            $this->link->setLogLevel(LogLevel::ERROR);
        }
    }

    public function debug($msg, $mixed = []) { $this->link->debug($msg, $mixed); }
    public function info($msg, $mixed = []) { $this->link->info($msg, $mixed); }
    public function notice($msg, $mixed = []) { $this->link->notice($msg, $mixed); }
    public function warning($msg, $mixed = []) { $this->link->warning($msg, $mixed); }
    public function error($msg, $mixed = []) { $this->link->error($msg, $mixed); }
    public function critical($msg, $mixed = []) { $this->link->critical($msg, $mixed); }
    public function alert($msg, $mixed = []) { $this->link->alert($msg, $mixed); }
    public function emergency($msg, $mixed = []) { $this->link->emergency($msg, $mixed); }
}