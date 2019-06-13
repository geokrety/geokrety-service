<?php
require_once '__sentry.php';
/***
 * Geokrety Service Entry point
 */
use Service\JobManager;

if (isset($argv)) {
    $manager = new JobManager($argv[1]);
    $manager->run();
}
