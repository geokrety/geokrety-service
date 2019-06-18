<?php
// Error reporting - do not report warn
error_reporting(E_ERROR | E_PARSE);

// Composer
$vendorDir = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'vendor'));
include_once $vendorDir.DIRECTORY_SEPARATOR.'autoload.php';