<?php

namespace Service;

class JobManager {
    private $jobName;

    public function __construct($name) {
        $this->jobName = $name;
    }

    public function run() {
        echo date('Y-m-d H:i:s'). " JobManager run $this->jobName\n";
        if ($this->jobName == "consistency") {
            $this->runConsistency();
        }
    }

    public function runConsistency() {
        $gkmConfig = [];
        $consistencyCheck = new GkmConsistencyCheck($gkmConfig);
        $consistencyCheck->run();
    }
}