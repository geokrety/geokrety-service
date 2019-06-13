<?php
function __autoload($class) {
    include_once join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'lib', str_replace('\\', DIRECTORY_SEPARATOR, $class))).'.php';
}

// Composer
// $vendorDir = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'vendor'));
// include_once $vendorDir.DIRECTORY_SEPARATOR.'autoload.php';