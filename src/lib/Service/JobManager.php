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
        $this->logger = new GKLogger(get_class($this));
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

    public function job_sandbox() {
        // https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/getting-started_basic-usage.html
        // https://docs.aws.amazon.com/fr_fr/sdk-for-php/v3/developer-guide/s3-examples-creating-buckets.html
        $bucket = 'gkm-sync';
        $accessKey = 'minioAK';
        $secretKey = 'miniominioSK';
        $minioEndpoint = 'http://192.168.99.100:9000';
        $s3 = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region'  => 'us-east-1',
                'endpoint' => $minioEndpoint,
                'use_path_style_endpoint' => true,
                'credentials' => [
                        'key'    => $accessKey,
                        'secret' => $secretKey,
                    ],
        ]);
        $this->logger->info("list bucket");
        $result = $s3->listBuckets();
        $names = $result->search('Buckets[].Name');
        foreach ($names as $name) {
            $this->logger->info(" * ".$name);
        }
        if (!in_array($bucket, $names)) {
            $this->logger->info("create bucket $bucket");
            $s3->createBucket(['Bucket' => $bucket]);
        }

        $this->logger->info("putObject ".__FILE__);
        // Send a PutObject request and get the result object.
        $insert = $s3->putObject([
             'Bucket' => $bucket,
             'Key'    => basename(__FILE__),
             'SourceFile'   => __FILE__
        ]);

        $this->logger->info("sandbox done");
    }
}