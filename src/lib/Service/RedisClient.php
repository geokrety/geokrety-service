<?php

namespace Service;

use Exception;

/**
 * RedisClient : redis read write access
 */
class RedisClient extends ConfigurableService {
    const CONFIG_REDIS_HOST = 'REDIS_HOSTNAME';
    const CONFIG_REDIS_PORT = 'REDIS_PORT';

    private static $_instance = null;

    protected $redisHost = "redis";
    protected $redisPort = 6379;

    private $link = null;

    private function __construct($config) {
        $this->initConfig($config, self::CONFIG_REDIS_HOST, "redisHost");
        $this->initConfig($config, self::CONFIG_REDIS_PORT, "redisPort");
    }

    public static function getInstance($config) {
        if (is_null(self::$_instance)) {
            self::$_instance = new RedisClient($config);
        }

        return self::$_instance;
    }

    public function connect() {
        if ($this->link != null) {
                return $this->link;
        }
        try {
            $this->link = new \Redis();
            $this->link->connect($this->redisHost, $this->redisPort);
            echo "Connection to REDIS $this->redisHost:$this->redisPort successfully\n";
            return $this->link;
        } catch (Exception $exc) {
            $errMsg = "Unable to connect to REDIS host:$this->redisHost port:$this->redisPort ".$exc->getMessage();
            throw new Exception($errMsg);
        }
    }

    public function get($redisKey) {
        return $this->link->get($redisKey);
    }

    public function set($redisKey, $value) {
        return $this->link->set($redisKey, $value);
    }

    public function getFromRedis($rollId, $gkId) {
        $redisKey = $this->buildRedisKey($rollId, $gkId);
        $jsonObject = $this->link->get($redisKey);
        return json_decode($jsonObject, true);
    }

    public function putInRedis($rollId, $gkId, $geokretyObject, $ttlSec) {
        $redisKey = $this->buildRedisKey($rollId, $gkId);
        $jsonObject = json_encode($geokretyObject);
        $this->link->set($redisKey, $jsonObject, $this->redisValueTimeToLiveSec);
    }

    private function buildRedisKey($rollId, $gkId) {
        return "gkm-roll-$rollId-gkid-$gkId";
    }
}