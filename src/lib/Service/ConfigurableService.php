<?php

namespace Service;

class ConfigurableService {
    private $config;

    protected function initConfig($config, $name, $attribute) {
        $this->config = $config;
        if (isset($config[$name])) {
            $this->$attribute = $config[$name];
        } else if (isset($_ENV[$name])) {
            $this->$attribute = $_ENV[$name];
        }
    }
}