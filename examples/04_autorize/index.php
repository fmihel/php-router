<?php

use fmihel\console;
use fmihel\router\exceptions\AccessDenied;
use fmihel\router\exceptions\ModuleIsNotDefined;
use fmihel\router\exceptions\TokenException;
use fmihel\router\input;
use fmihel\router\response;
use fmihel\router\router;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/my-session.php';

try {

    if (router::enable()) {

        // throw new \Exception('custom error');
        session::autorizeByToken(input::token());

        $module = router::module([__DIR__]);
        if (! file_exists($module)) {
            throw new ModuleIsNotDefined($module);
        }
        require_once $module;

    }
} catch (TokenException $e) {
    response::error($e, response::HTTP_UNAUTORIZED_401);
} catch (AccessDenied $e) {
    response::error($e, response::HTTP_UNAUTORIZED_401);
} catch (ModuleIsNotDefined $e) {
    response::error($e, response::HTTP_NOT_FOUND_404);
} catch (\Exception $e) {

    console::log($e);
    response::error($e);
}

response::html(__DIR__ . '/index.html');
