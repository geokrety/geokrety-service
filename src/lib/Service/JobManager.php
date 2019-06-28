<?php

namespace Service;

use Exception;
use Service\Consistency\GkmConsistencyCheck;


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
            if ($this->jobName == "consistency") {
                $this->runConsistency($this->jobOptions);
                return;
            }
            throw new Exception("job name $this->jobName not yet implemented");
        } catch (Exception $exception) {
            $this->logger->error("unexpected error ".$exception->getMessage(),['job' => $this->jobName]);
            $this->logger->debug("unexpected error",['job' => $this->jobName, 'exception' => $exception]);
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

}