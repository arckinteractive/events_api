<?php

define('PHPUNIT_ELGG_TESTING_APPLICATION', true);

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/vendor/autoload.php';
require_once dirname(__DIR__) . "/autoloader.php";

\Elgg\TestCase::bootstrap();