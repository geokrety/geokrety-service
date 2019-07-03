<?php

namespace Geokrety\Service\Consistency;

use Prometheus\Storage\InMemory;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\PushGateway;

/**
 * GkmMetricsPublisher : collect and publish geokrety GkmSync metrics
 */
class GkmMetricsPublisher {
    private $pushGatewayHostPort;
    private $job = "GkmMetricsPublisher";
    private $namespace = "gkm";

    private $registry;

    public function __construct($pushGatewayHostPort = 'pushgateway:9091') {
        $this->pushGatewayHostPort = $pushGatewayHostPort;
        $this->registry = new CollectorRegistry(new InMemory());
    }

    public function gkmSyncPublish($rollId,
                                   $gkNumber, $gkmNumber,
                                   $notFoundCount, $wrongNameCount, $wrongOwnerCount, $wrongDistCount,
                                   $downloadTimeMs, $compareTimeMs) {
        $name = "gkmSync";
        $help = "sync between gk db and gkm export";

        //~ sync results
        $syncResultGauge = $this->registry->registerGauge($this->namespace, $name, $help, ['rollId', 'counter']);
        $syncResultGauge->set($gkNumber, [$rollId, 'gkNumber']);
        $syncResultGauge->set($gkmNumber, [$rollId, 'gkmNumber']);
        $syncResultGauge->set($notFoundCount, [$rollId, 'notFound']);
        $syncResultGauge->set($wrongNameCount, [$rollId, 'wrongName']);
        $syncResultGauge->set($wrongOwnerCount, [$rollId, 'wrongOwner']);
        $syncResultGauge->set($wrongDistCount, [$rollId, 'wrongDist']);

        //~ sync performances : execution
        $syncTimeGauge = $this->registry->registerGauge($this->namespace, $name, $help, ['rollId', 'time']);
        $syncResultGauge->set($downloadTimeMs, [$rollId, 'download']);
        $syncResultGauge->set($compareTimeMs, [$rollId, 'compare']);
    }

    public function publish() {
        $pushGateway = new PushGateway($this->pushGatewayHostPort);
        $pushGateway->push($this->registry, $this->job, array());
    }
}
