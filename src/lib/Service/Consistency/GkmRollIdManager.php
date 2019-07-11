<?php

namespace Service\Consistency;

use Service\ConfigurableService;
use Service\RedisClient;
use Service\GKLogger;

/**
 * GkmExportDownloader : download GeoKretyMap export into redis
 */
class GkmRollIdManager extends ConfigurableService {
    const CONFIG_CONSISTENCY_ROLL_MIN_DAYS = 'gkm_consistency_roll_min_days';

    const REDIS_ROLL_ID = 'gkm_roll_id';
    const REDIS_ROLL_TIMESTAMP = 'gkm_roll_timestamp';

    const DAY_IN_SECOND = 60*60*24;

    private $rollMinDays = 7;
    private $redisConnection;
    private $logger;

    public function __construct($config) {
        $this->initConfig($config, self::CONFIG_CONSISTENCY_ROLL_MIN_DAYS, "rollMinDays");
        $this->redis = RedisClient::getInstance($config);
        $this->redis->connect();
        $this->logger = new GKLogger(get_class($this));
    }

    public function getRollMinDays() {
        return intval($this->rollMinDays);
    }

    public function giveMeARollId() {
        $redisRollId = $this->getRollId();
        $redisRollTimestamp = $this->getRollTimestamp();
        $redisRollTimestampStr = $this->dateStr($redisRollTimestamp);
        $this->logger->debug("redis was rollId:$redisRollId, timestamp:$redisRollTimestamp -> $redisRollTimestampStr");
        if ($redisRollId == "" || $redisRollTimestamp == "") {
            $this->logger->debug("first launch!", ["rollId" => 1]);
            $this->setRollId(1);
            $this->setRollTimestamp(-1);
            return 1;
        }
        if ($redisRollId < 0 || $redisRollTimestamp < 0) {
            return -1;
        }
        $minTimestamp = $redisRollTimestamp + ($this->rollMinDays * self::DAY_IN_SECOND);
        $minTimestampStr = $this->dateStr($minTimestamp);
        if ($minTimestamp <= time()) {
            $this->setRollId($redisRollId+1);
            $this->setRollTimestamp(-1);
            return $redisRollId+1;
        }
        $this->logger->info("No job before $minTimestampStr");
        return -2;
    }

    public function enforceARollId() {
        $redisRollId = $this->getRollId();
        $redisRollTimestamp = $this->getRollTimestamp();
        if ($redisRollId == "" || $redisRollId < 0) {
            $this->logger->info("enforce", ["rollId" => 1]);
            return 1;
        }
        $rollId = $redisRollId+1;
        $this->logger->info("enforce", ["rollId" => $rollId]);
        return $rollId;
    }

    public function getRollId() {
        return $this->redis->get(self::REDIS_ROLL_ID);
    }

    public function getRollTimestamp() {
        return $this->redis->get(self::REDIS_ROLL_TIMESTAMP);
    }

    public function setRollId($rollId) {
        return $this->redis->set(self::REDIS_ROLL_ID, $rollId);
    }

    public function setRollTimestamp($timestamp) {
        return $this->redis->set(self::REDIS_ROLL_TIMESTAMP, $timestamp);
    }

    public function endARollId($rollId) {
        $this->setRollId($rollId);
        $this->setRollTimestamp(time());
    }

    public function dateStr($ts) {
        return date("Y-m-d H:i:s", $ts);
    }
}