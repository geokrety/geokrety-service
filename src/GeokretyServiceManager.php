<?php
require_once '__sentry.php';
/***
 * Geokrety Service Entry point
 */
use Service\JobManager;

if (isset($argv)) {
    $manager = new JobManager(array_slice($argv,1));
    $manager->run();
}
