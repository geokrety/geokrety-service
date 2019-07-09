<?php

namespace Service;

use Service\Consistency\GkmConsistencyCheck;
use Service\HealthJob;
use Exception;

class JobManager {
    private $jobName;
    private $jobOptions;
    private $logger;

    public function __construct($args) {
        $this->jobName = $args[0];
        $this->jobOptions = array_slice($args, 1);
        $this->logger = GKLogger::getInstance();
    }

    public function run() {
        try {
            if (!isset($this->jobName)) {
                throw new Exception("job name expected as first argument");
            }
            $jobMethodName = 'job_'.$this->jobName;
            if (!method_exists($this, $jobMethodName)) {
                throw new Exception("job name $this->jobName not yet implemented");
            }
            // call method $this->job_{{jobName}}()
            call_user_func(array($this, $jobMethodName));
        } catch (Exception $exception) {
            $this->logger->error("unexpected error ".$exception->getMessage(),['job' => $this->jobName]);
            $this->logger->debug("unexpected error",['job' => $this->jobName, 'exception' => $exception]);
        }
    }

    public function job_health() {
        $healthJob = new HealthJob();
        $healthJob->run();
    }

    public function job_consistency() {
        $gkmConfig = [];
        if (in_array("force", $this->jobOptions)) {
            $gkmConfig[GkmConsistencyCheck::CONFIG_CONSISTENCY_ENFORCE] = true;
        }
        $consistencyCheck = new Consistency\GkmConsistencyCheck($gkmConfig);
        $consistencyCheck->run();
    }

}