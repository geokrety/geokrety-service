<?php

namespace Service;

use Exception;

/**
 * MinIOClient : MinIO read write access
 */
class MinIOClient extends ConfigurableService {
    const CONFIG_MINIO_HOST = 'MINIO_HOSTNAME';
    const CONFIG_MINIO_PORT = 'MINIO_PORT';
    const CONFIG_MINIO_ACCESS_KEY = 'MINIO_ACCESS_KEY';
    const CONFIG_MINIO_SECRET_KEY = 'MINIO_SECRET_KEY';

    private static $_instance = null;

    protected $minioHost = "minio";
    protected $minioPort = 9000;
    protected $minioAccessKey = "minioAK";
    protected $minioSecretKey = "miniominioSK";

    private $link = null;
    private $logger;

    private function __construct($config) {
        $this->initConfig($config, self::CONFIG_MINIO_HOST, "minioHost");
        $this->initConfig($config, self::CONFIG_MINIO_PORT, "minioPort");
        $this->initConfig($config, self::CONFIG_MINIO_ACCESS_KEY, "minioAccessKey");
        $this->initConfig($config, self::CONFIG_MINIO_SECRET_KEY, "minioSecretKey");
        $this->logger = new GKLogger(get_class($this));
    }

    public static function getInstance($config) {
        if (is_null(self::$_instance)) {
            self::$_instance = new MinIOClient($config);
        }

        return self::$_instance;
    }

    public function connect() {
        if ($this->link != null) {
                return $this->link;
        }
        try {
            $minioEndpoint = "http://".$this->minioHost.":".$this->minioPort;
            $this->link = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region'  => 'us-east-1',
                'endpoint' => $minioEndpoint,
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key'    => $this->minioAccessKey,
                    'secret' => $this->minioSecretKey,
                ],
            ]);
            $this->logger->debug("Connection to MinIO successfully",['host' => $this->minioHost, "port" =>  $this->minioPort]);
            return $this->link;
        } catch (Exception $exc) {
            $errMsg = "Unable to connect to MinIO host:$this->minioHost port:$this->minioPort ".$exc->getMessage();
            $this->logger->error("Connection to MinIO failed ".$exception->getMessage(),['host' => $this->minioHost, 'port' =>  $this->minioPort]);
            $this->logger->debug("Connection to MinIO failed",['host' => $this->minioHost, 'port' =>  $this->minioPort, 'exception' => $exception]);
            throw new Exception($errMsg);
        }
    }

    public function listBucketsNames() {
        $result = $this->link->listBuckets();
        return $result->search('Buckets[].Name');
    }


    public function bucketExist($bucketName) {
        $result = $this->link->listBuckets();
        $names = $result->search('Buckets[].Name');
        return (in_array($bucketName, $names));
    }

    public function createBucket($bucketName) {
        return $this->link->createBucket(['Bucket' => $bucketName]);
    }

    public function ensureBucketExist($bucketName, $mustCreateBucket = true) {
        $bucketExist = $this->bucketExist($bucketName);
        if (!$bucketExist && !$mustCreateBucket) {
            throw new Exception("bucket $bucketName doesn't exist");
        } else if (!$bucketExist) {
            $this->createBucket($bucketName);
        }
    }

    public function putFileInBucket($bucketName, $objectKey, $fileName, $mustCreateBucket = true) {
        $this->ensureBucketExist($bucketName, $mustCreateBucket);
        return $this->link->putObject([
          'Bucket' => $bucketName,
          'Key'    => $objectKey,
          'SourceFile' => $fileName
        ]);
    }

}