<?php

namespace Service;

use Service\GKLogger;

class HealthJob {
    private $logger;

    public function __construct() {
        $this->logger = GKLogger::getInstance();
    }

    public function run() {
        $this->logger->info(".");
    }
}
