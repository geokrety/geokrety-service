<?php

namespace Service\Consistency;

use Service\ConfigurableService;
use Service\GKLogger;

use Prometheus\Storage\InMemory;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\PushGateway;

/**
 * GkmMetricsPublisher : collect and publish geokrety GkmSync metrics
 */
class GkmMetricsPublisher extends ConfigurableService {
    const CONFIG_PUSH_GATEWAY_HOST = 'PGA_HOST';
    const CONFIG_PUSH_GATEWAY_PORT = 'PGA_PORT';

    private $job = "GkmMetricsPublisher";
    private $namespace = "gkm";
    private $logger;
    private $registry;

    private $pushGatewayHost = 'pushgateway';
    private $pushGatewayPort = 9091;

    public function __construct($config) {
        $this->initConfig($config, self::CONFIG_PUSH_GATEWAY_HOST, "pushGatewayHost");
        $this->initConfig($config, self::CONFIG_PUSH_GATEWAY_PORT, "pushGatewayPort");
        $this->logger = GKLogger::getInstance();
    }

    public function gkmSyncMetrics($rollId, $gkNumber, $gkmNumber, $wrongCount, $downloadTimeSec, $compareTimeSec) {
        $help = "sync between gk db and gkm export";
        $this->registry = new CollectorRegistry(new InMemory());
        $this->logger->info("gkmSyncMetrics", [
            'rollId' => $rollId,
            'gkNumber' => $gkNumber,
            'gkmNumber' => $gkmNumber,
            'wrongCount' => $wrongCount,
            'downloadTimeSec' => $downloadTimeSec,
            'compareTimeSec' => $compareTimeSec,
            'pushGateway' => $this->getPushGatewayHostPort()
        ]);
        //~ sync results
        $syncResultGauge = $this->registry->registerGauge($this->namespace, "gkmSyncResult", $help, ['rollId', 'counter']);
        $syncResultGauge->set($gkNumber, [$rollId, 'gkNumber']);
        $syncResultGauge->set($gkmNumber, [$rollId, 'gkmNumber']);
        $syncResultGauge->set($wrongCount, [$rollId, 'wrongCount']);

        //~ sync performances : execution
        $syncTimeGauge = $this->registry->registerGauge($this->namespace, "gkmSyncTime", $help, ['rollId', 'time']);
        $syncTimeGauge->set($downloadTimeMs, [$rollId, 'download']);
        $syncTimeGauge->set($compareTimeMs, [$rollId, 'compare']);
    }

    public function publish() {
        $pushGateway = new PushGateway($this->getPushGatewayHostPort());
        $pushGateway->push($this->registry, $this->job, array());
    }

    public function getPushGatewayHostPort() {
        return $this->pushGatewayHost.':'.$this->pushGatewayPort;
    }
}
