<?php

use fmihel\router\response;
use fmihel\router\router;

require_once __DIR__ . '/../../vendor/autoload.php';

response::preflight();

try {

    if (router::enable()) {

        $module = router::module([__DIR__]);
        require_once $module;
    }

} catch (\Throwable $th) {
    response::error();
}
