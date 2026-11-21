<?php

use fmihel\router\response;
use fmihel\router\router;

require_once __DIR__ . '/../../vendor/autoload.php';

if (router::enable()) {

    $module = router::module([__DIR__]);
    require_once $module;

}

response::html(__DIR__ . '/index.html');
