<?php

namespace Service;

class ConfigurableService {
    private $config;

    protected function initConfig($config, $name, $attribute) {
        $this->config = $config;
        $envConfig = getenv($name);
        if (isset($config[$name])) {
            $this->$attribute = $config[$name];
        } else if ($envConfig) {
            $this->$attribute = $envConfig;
        }
    }
}