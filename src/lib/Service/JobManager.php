<?php

namespace Service;

use Exception;
use Service\Consistency\GkmConsistencyCheck;


class JobManager {
    private $jobName;
    private $jobOptions;

    public function __construct($args) {
        $this->jobName = $args[0];
        $this->jobOptions = array_slice($args, 1);
    }

    public function run() {
        try {
            if (!isset($this->jobName)) {
                throw new Exception("job name expected as first argument");
            }
            if ($this->jobName == "consistency") {
                $this->runConsistency($this->jobOptions);
                return;
            }
            throw new Exception("job name $this->jobName not yet implemented");
        } catch (Exception $exception) {
            $this->log("unexpected error: ".$exception->getMessage());
        }
    }

    public function runConsistency($options) {
        $gkmConfig = [];
            if (in_array("force", $options)) {
                $gkmConfig[GkmConsistencyCheck::CONFIG_CONSISTENCY_ENFORCE] = true;
            }
            $consistencyCheck = new GkmConsistencyCheck($gkmConfig);
            $consistencyCheck->run();
    }

    public function log($msg) {
        echo date('Y-m-d H:i:s')."#$this->jobName | $msg\n";
    }
}