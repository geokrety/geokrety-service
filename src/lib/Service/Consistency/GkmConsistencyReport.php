<?php

namespace Service\Consistency;

use \Azurre\Component\Logger;
use \Azurre\Component\Logger\Handler\File;

/**
 * GkmConsistencyReport : for a given roll, report consistency between GeoKrety and GeoKretyMap
 */
class GkmConsistencyReport {
    private $reportFile = "__gkmConsistencyReport.txt";
    private $logger;

    public function __construct($rollId = null) {
        if ($rollId != null) {
            $this->reportFile = "__gkmConsistencyReport_$rollId.txt";
        }
        $this->logger = new Logger();
        $this->logger->setHandler(new File($this->reportFile));
        $this->logger->info("starting report", ['rollId' => $rollId]);
    }

    public function downloadDone($downloadTimeSec) {
        $this->logger->info("download done", ['time' => $downloadTimeSec]);
    }

    public function diffNotOnGkm($gkId) {
        $this->logger->info("DIFF missing", ['gkId' => $gkId]);
    }

    public function diffNotSameName($gkId, $gkName, $gkmName) {
        $this->logger->info("DIFF name", ['gkId' => $gkId, 'gkName' => $gkName, 'gkmName' => $gkmName]);
    }

    public function diffNotSameOwnerId($gkId, $gkOwnerId, $gkmOwnerId) {
        $this->logger->info("DIFF owner", ['gkId' => $gkId, 'gkOwnerId' => $gkOwnerId, 'gkmOwnerId' => $gkmOwnerId]);
    }

    public function diffNotSameDistance($gkId, $gkDistanceKm, $gkmDistanceKm) {
        $this->logger->info("DIFF distance", ['gkId' => $gkId, 'gkDistanceKm' => $gkDistanceKm, 'gkmDistanceKm' => $gkmDistanceKm]);
    }

    public function compareDone($geokretyCount, $wrongGeokretyCount, $compareTimeSec) {
        $this->logger->info("compare done", ['geokretyCount' => $geokretyCount, 'wrongGeokretyCount' => $wrongGeokretyCount, 'time' => $compareTimeSec]);
    }

    public function getReportFile() {
        return $this->reportFile;
    }
}