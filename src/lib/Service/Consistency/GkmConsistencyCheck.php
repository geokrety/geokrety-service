<?php

namespace Service\Consistency;

use Service\ConfigurableService;
use Service\ExecutionTime;
use Service\GKDB; // create geokrety shared library ?
use Service\GKLogger;
use Service\RedisClient;

use Gkm\Gkm;
use Gkm\Domain\GeokretyNotFoundException;

/**
 * GkmConsistencyCheck : analyse consistency between GeoKrety database and GeoKretyMap service
 */
class GkmConsistencyCheck extends ConfigurableService {
    const CONFIG_CONSISTENCY_ENFORCE = 'GKM_CONSISTENCY_ENFORCE';
    const CONFIG_API_ENDPOINT = 'GKM_API_ENDPOINT';

    //~ config
    protected $apiEndpoint = "https://api.geokretymap.org";
    protected $enforce= false;

    //~ private
    private $rollId = 0;
    private $job = "GKMConsistencyCheck";
    private $gkm;// gkm api client

    private $gkmRollIdManager;
    private $gkmMetricsPublisher;
    private $gkmReport;

    private $currentId = null;

    private $logger;
    private $logContext = [];

    public function __construct($config) {
        $this->initConfig($config, self::CONFIG_API_ENDPOINT, "apiEndpoint");
        $this->initConfig($config, self::CONFIG_CONSISTENCY_ENFORCE, "enforce");
        $this->logger = new GKLogger(get_class($this));
        $this->redis = RedisClient::getInstance($config);
        $this->redis->connect();
        $this->gkmExportDownloader = new GkmExportDownloader($config);
        $this->gkm = new Gkm();// no more used
        $this->gkmRollIdManager = new GkmRollIdManager($config);
        $this->gkmMetricsPublisher = new GkmMetricsPublisher($config);
    }

    public function run() {
        $runExecutionTime = new ExecutionTime();
        $executionTime = new ExecutionTime();

        $runExecutionTime->start();

        $this->rollId = $this->gkmRollIdManager->giveMeARollId();
        $this->logContext["rollId"] = $this->rollId;
        if ($this->rollId <= 0 && !$this->enforce) {
            $this->logger->info("nothing to do", $this->logContext);
            return;
        } else if ($this->rollId <= 0) {
            $this->rollId = $this->gkmRollIdManager->enforceARollId();
            $this->logContext["rollId"] = $this->rollId;
            $this->logContext["enforce"] = true;
        }
        //~ let's create a new consistency report
        $this->gkmReport = new GkmConsistencyReport($this->rollId);

        $executionTime->start();
        $gkmCount = $this->gkmExportDownloader->run($this->rollId);
        $this->logContext["gkmCount"] = $gkmCount;
        $executionTime->end();
        $this->logger->info("download and put $gkmCount in redis $executionTime", $this->logContext);
        $downloadTimeSec = $executionTime->durationSec();

        $this->gkmReport->downloadDone($downloadTimeSec);

        $executionTime->start();
        $batchSize = 50;
        $batchCount = 2000;
        $endOfTable = false;
        $geokretyCount = 0;
        $wrongGeokretyCount = 0;

        for ($i=0;!$endOfTable && $i<$batchCount;$i++) {

            $geokrets = $this->collectNextGeokretyToSync($batchSize);
            $geokretsCount = count($geokrets);
            $endOfTable = ($geokretsCount == 0);

            $this->logger->debug("$i ) $geokretsCount geokrets", $this->logContext);

            foreach  ($geokrets as $geokrety) {
                $geokretyCount++;
                $isValid = $this->compareGeokretyWithRedis($this->rollId, $geokrety);
                if (!$isValid) {
                  $wrongGeokretyCount++;
                }
                $this->logContext["gkCount"] = $geokretyCount;
                $this->logContext["syncDiff"] = $wrongGeokretyCount;
            }
            flush();
            // DEBUG // echo $this->objectToHtml($gkmGeokrets);
        }
        $executionTime->end();
        $this->logger->info("compare $geokretyCount geokrety ($wrongGeokretyCount are invalids) - $executionTime", $this->logContext);
        $compareTimeSec = $executionTime->durationSec();
        $this->gkmReport->compareDone($geokretyCount, $wrongGeokretyCount, $compareTimeSec);

        $runExecutionTime->end();
        $this->logger->info("TOTAL: $runExecutionTime", $this->logContext);

        $this->gkmRollIdManager->endARollId($this->rollId);
        $this->gkmMetricsPublisher->gkmSyncMetrics($this->rollId, $geokretyCount, $gkmCount, $wrongGeokretyCount,
            $downloadTimeSec, $compareTimeSec);
        $this->gkmMetricsPublisher->publish();
    }


    private function compareGeokretyWithRedis($rollId, $geokretyObject) {
        $rollId = $this->rollId;
        $gkId = $geokretyObject["id"];
        $gkName = $geokretyObject["nazwa"];
        $gkOwnerId = $geokretyObject["owner"];
        $gkDistanceKm = $geokretyObject["droga"];


        $gkmObject = $this->redis->getFromRedis($rollId, $gkId);
        $gkmName = $gkmObject["name"];
        $gkmOwnerId = $gkmObject["owner_id"];
        $gkmDistanceKm = $gkmObject["dist"];

        // compare $geokretyObject from database /vs/ $gkmObject (redis cache) from last export
        if (!isset($gkmObject) || $gkmObject == null) {
            $this->logger->debug(" #$rollId X $gkId missing on GKM side", $this->logContext);
            $this->gkmReport->diffNotOnGkm($gkId);
            return false;
        }

        if ($gkName != $gkmName) {
            // 'x90' char make docker toolbox console to leave // https://github.com/docker/toolbox/issues/695
            $this->logger->debug(" #$rollId X $gkId not the same name on GKM side", $this->logContext);
            $this->gkmReport->diffNotSameName($gkId, $gkName, $gkmName);
            return false;
        }

        if ($gkOwnerId != $gkmOwnerId) {
            $this->logger->debug(" #$rollId X $gkId not the same owner id($gkOwnerId) on GKM side($gkmOwnerId)", $this->logContext);
            $this->gkmReport->diffNotSameOwnerId($gkId, $gkOwnerId, $gkmOwnerId);
            return false;
        }

        if ($gkDistanceKm != $gkmDistanceKm) {
            $this->logger->debug(" #$rollId X $gkId not the same distance traveled($gkDistanceKm) on GKM side($gkmDistanceKm)", $this->logContext);
            $this->gkmReport->diffNotSameDistance($gkId, $gkDistanceKm, $gkmDistanceKm);
            return false;
        }
        // DEBUG // $this->logger->debug(" #$rollId * $gkId OK", $this->logContext);
        return true;
    }

    private function collectNextGeokretyToSync($batchSize = 50) { // 30 SOMETIME OK // 50 RESULT IN 503
        $link = GKDB::getLink($this->config);
        $where = "";
        if ($this->currentId != null) {
            $where = "WHERE `id` > ?";
        }
$sql = <<<EOQUERY
        SELECT    `id`,`nazwa`,`owner`,`droga`
        FROM      `gk-geokrety`
        $where
        ORDER BY id ASC
        LIMIT $batchSize
EOQUERY;
        // DEBUG // echo "$sql - id:$this->currentId\n";

        if (!($stmt = $link->prepare($sql))) {
            throw new \Exception($action.' prepare failed: ('.$this->dblink->errno.') '.$this->dblink->error);
        }
        if ($this->currentId != null && !$stmt->bind_param('i', $this->currentId)) {
            throw new \Exception($action.' binding parameters failed: ('.$stmt->errno.') '.$stmt->error);
        }
        if (!$stmt->execute()) {
            throw new \Exception($action.' execute failed: ('.$stmt->errno.') '.$stmt->error);
        }
        $stmt->store_result();
        $nbRow = $stmt->num_rows;

        $geokrets = array();

        if ($nbRow == 0) {
            return $geokrets;
        }

        // associate result vars
        $stmt->bind_result($gkId, $nazwa, $owner, $droga);

        while ($stmt->fetch()) {
            $geokret = [];
            // DEBUG // echo "$gkId\n";
            $geokret["id"] = $gkId;
            $geokret["nazwa"] = $nazwa;
            $geokret["owner"] = $owner;
            $geokret["droga"] = $droga;
            $this->currentId = $gkId;
            array_push($geokrets, $geokret);
        }

        $stmt->close();

        return $geokrets;
    }

    // deprecated
    private function collectGKMGeokretyOneByOne($geokrets = []) {
        $gkmGeokrets = [];
        foreach ($geokrets as $geokrety ) {
            $gkId = $geokrety["id"];
            // DEBUG //  echo $gkId."\n";
            try {
              $gkmGeokrety = $geokrety = $this->gkm->getGeokretyById($gkId);
              array_push($gkmGeokrets, $gkmGeokrety);
            } catch (GeokretyNotFoundException $notFoundException) {
              $this->logMissingGkm($gkId);
            }
        }
        return $gkmGeokrets;
    }

    // deprecated
    private function collectGKMGeokretyBulk($geokrets = []) {
        $gkmGeokrets = [];
        $idsOnly = [];
        foreach ($geokrets as $geokrety ) {
            array_push($idsOnly, $geokrety["id"]);
        }
        return $geokrety = $this->gkm->getGeokretyByIds($idsOnly);
    }

    // deprecated
    private function logMissingGkm($geokretyId) {
      echo "missing geokrety id=$geokretyId on GKM side\n";
    }


    private function objectToHtml($var) {
       $rep = print_r($var, true);
       return '<pre>' . htmlentities($rep) . '</pre>';
    }

}
