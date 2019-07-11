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
    const CONFIG_PUSH_GATEWAY_HOST = 'PUSH_GW_HOST';
    const CONFIG_PUSH_GATEWAY_PORT = 'PUSH_GW_PORT';

    private $job = "GkmMetricsPublisher";
    private $namespace = "gkm";
    private $logger;
    private $registry;

    protected $pushGatewayHost = 'pushgateway';
    protected $pushGatewayPort = 9091;

    public function __construct($config) {
        $this->initConfig($config, self::CONFIG_PUSH_GATEWAY_HOST, "pushGatewayHost");
        $this->initConfig($config, self::CONFIG_PUSH_GATEWAY_PORT, "pushGatewayPort");
        $this->logger = new GKLogger(get_class($this));
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
        $syncResultGauge->set((int)$gkNumber, [$rollId, 'gkNumber']);
        $syncResultGauge->set((int)$gkmNumber, [$rollId, 'gkmNumber']);
        $syncResultGauge->set((int)$wrongCount, [$rollId, 'wrongCount']);

        //~ sync performances : execution
        $syncTimeGauge = $this->registry->registerGauge($this->namespace, "gkmSyncTime", $help, ['rollId', 'time']);
        $syncTimeGauge->set(floatval($downloadTimeSec), [$rollId, 'download']);
        $syncTimeGauge->set(floatval($compareTimeSec), [$rollId, 'compare']);
    }

    public function publish() {
        $pushGateway = new PushGateway($this->getPushGatewayHostPort());
        $pushGateway->push($this->registry, $this->job, array());
    }

    public function getPushGatewayHostPort() {
        return $this->pushGatewayHost.':'.$this->pushGatewayPort;
    }
}
