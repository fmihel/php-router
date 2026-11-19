<?php

use fmihel\console;
use fmihel\router\exceptions\ModuleIsNotDefined;
use fmihel\router\response;
use fmihel\router\router;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__.'/../loader.php';

try {

    if (router::enable()) {

        $module = router::module([__DIR__]);
        if (! file_exists($module)) {
            throw new ModuleIsNotDefined($module);
        }
        require_once $module;

    }
} catch (ModuleIsNotDefined $e) {
    response::error($e, 404);
} catch (\Exception $e) {

    console::log($e);
    response::error($e);
}

response::html(__DIR__ . '/index.html');
