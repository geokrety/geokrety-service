<?php

namespace Service;

use Exception;
use Service\Consistency\GkmConsistencyCheck;


class JobManager {
    private $jobName;

    public function __construct($name) {
        $this->jobName = $name;
    }

    public function run() {
        try {
            $this->log("run");
            if ($this->jobName == "consistency") {
                $this->runConsistency();
            }
        } catch (Exception $exception) {
            $this->log("unexpected error: ".$exception->getMessage());
        }
    }

    public function runConsistency() {
        $gkmConfig = [];
            $consistencyCheck = new GkmConsistencyCheck($gkmConfig);
            $consistencyCheck->run();
    }

    public function log($msg) {
        echo date('Y-m-d H:i:s')."#$this->jobName | $msg\n";
    }
}