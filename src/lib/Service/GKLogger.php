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

    private static $_instance = null;

    protected $logFile = 'php://stdout';
    protected $logLevel = 'DEBUG';

    private $link = null;

    private function __construct() {
        $this->initConfig([], self::CONFIG_LOG_FILE, "logFile");
        $this->initConfig([], self::CONFIG_LOG_LEVEL, "logLevel");
        // DEBUG // echo "logFile:$this->logFile, logLevel:$this->logLevel\n";

        $this->link = new Logger();
        $this->link->setHandler(new File($this->logFile));

        // by default log all
        if ($this->logLevel == "WARNING" || $this->logLevel == "WARN") {
            $this->link->setLogLevel(LogLevel::WARNING);
        } else if ($this->logLevel == "ERROR") {
            $this->link->setLogLevel(LogLevel::ERROR);
        }
    }

    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new GKLogger();
        }

        return self::$_instance;
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