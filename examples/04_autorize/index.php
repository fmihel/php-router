<?php

use fmihel\console;
use fmihel\router\exceptions\AccessDenied;
use fmihel\router\exceptions\InvalidRoute;
use fmihel\router\exceptions\ModuleIsNotDefined;
use fmihel\router\exceptions\NotConfigured;
use fmihel\router\response;
use fmihel\router\router;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/my-session.php';

// CORS: белый список или '*' задаётся в config.php
response::$corsOrigins     = config['cors'] ?? '*';
response::$corsCredentials = config['corsCredentials'] ?? false;

// Завершаем preflight-запрос (OPTIONS), если пришёл
response::preflight();

try {

    if (router::enable()) {

        // throw new \Exception('custom error');

        // Авторизация из запроса: Bearer-заголовок или httpOnly-cookie (+ CSRF)
        session::autorizeFromRequest();

        $module = router::module([__DIR__]);
        if (! file_exists($module)) {
            throw new ModuleIsNotDefined($module);
        }
        require_once $module;

    }
} catch (AccessDenied $e) {
    response::error($e, response::HTTP_UNAUTORIZED_401);
} catch (InvalidRoute $e) {
    response::error($e, response::HTTP_NOT_FOUND_404);
} catch (ModuleIsNotDefined $e) {
    response::error($e, response::HTTP_NOT_FOUND_404);
} catch (NotConfigured $e) {
    console::log($e);
    response::error($e, response::HTTP_INTERBAL_SERVER_ERROR_500);
} catch (\Exception $e) {

    console::log($e);
    response::error($e);
}
