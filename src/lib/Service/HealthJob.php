<?php

namespace Service;

use Service\GKLogger;

class HealthJob {
    private $logger;

    public function __construct() {
        $this->logger = new GKLogger(get_class($this));
    }

    public function run() {
        $this->logger->info(".");
    }
}
