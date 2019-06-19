<?php

namespace Service;

use Exception;

class GKDB extends ConfigurableService {
    const CONFIG_DATABASE_USERNAME = 'DB_USERNAME';
    const CONFIG_DATABASE_PASSWORD = 'DB_PASSWORD';
    const CONFIG_DATABASE_HOSTNAME = 'DB_HOSTNAME';
    const CONFIG_DATABASE_BASENAME = 'DB_NAME';
    const CONFIG_DATABASE_TIMEZONE = 'DB_TIMEZONE';
    const CONFIG_DATABASE_CHARSET = 'DB_CHARSET';

    private static $connectCount = 0;
    private static $_instance = null;

    protected $dbUsername = "geokrety";
    protected $dbPassword = null;
    protected $dbHostname = "db";
    protected $dbBasename = "geokrety-db";
    protected $dbTimezone = "UTC";
    protected $dbCharset = "utf8";

    private $link = null;

    private function __construct($config) {
        $this->initConfig($config, self::CONFIG_DATABASE_USERNAME, "dbUsername");
        $this->initConfig($config, self::CONFIG_DATABASE_PASSWORD, "dbPassword");
        $this->initConfig($config, self::CONFIG_DATABASE_HOSTNAME, "dbHostname");
        $this->initConfig($config, self::CONFIG_DATABASE_BASENAME, "dbBasename");
        $this->initConfig($config, self::CONFIG_DATABASE_TIMEZONE, "dbTimezone");
        $this->initConfig($config, self::CONFIG_DATABASE_CHARSET, "dbCharset");
    }

    public static function getInstance($config) {
        if (is_null(self::$_instance)) {
            self::$_instance = new GKDB($config);
        }

        return self::$_instance;
    }

    public static function getLink($config) {
        return self::getInstance($config)->connect();
    }

    public static function getConnectCount() {
        return self::$connectCount;
    }

    public function connect() {
        if ($this->link != null) {
            try {
                mysqli_get_server_info($this->link);

                return $this->link;
            } catch (Exception $exc) {
            }
        }
        // DEBUG //
        echo 'connect '.$this->dbUsername.'@'.$this->dbHostname.' using '.$this->dbPassword.' basename:'.$this->dbBasename.' timezone:'.$this->dbTimezone.' charset:'.$this->dbCharset;
        try {
            $this->link = mysqli_connect($this->dbHostname, $this->dbUsername, $this->dbPassword);
            if (!$this->link) {// lets retry
                $this->link = mysqli_connect($this->dbHostname, $this->dbUsername, $this->dbPassword);
                if (!$this->link) {
                    throw new Exception('Unable to join database server');
                }
            }
            $db_select = mysqli_select_db($this->link, $this->dbBasename);
            if (!$db_select) {
                throw new Exception('Unknown database "'.$this->dbBasename.'" : '.mysqli_errno($this->link));
            }
            $this->link->set_charset($this->dbCharset);
            $this->link->query("SET time_zone = '".$this->dbTimezone."'");
            ++self::$connectCount;

            return $this->link;
        } catch (Exception $exc) {
            $errorId = uniqid('GKIE_');
            $errorMessage = 'DB ERROR '.$errorId.' - '.$exc->getMessage();
            error_log($errorMessage);
            error_log($exc);
            $this->link = null; // do not reuse link on error
            throw new Exception($errorMessage);
        }
    }

    public function close() {
        if (!isset($this->link)) {
            return;
        }
        mysqli_close($this->link);
        unset($this->link);
    }

    public function __clone() {
        throw new Exception("Can't clone a singleton");
    }
}
