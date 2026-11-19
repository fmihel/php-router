<?php

use fmihel\console;
use fmihel\router\exceptions\AccessDenied;
use fmihel\router\exceptions\InvalidRoute;
use fmihel\router\exceptions\ModuleIsNotDefined;
use fmihel\router\exceptions\NotConfigured;
use fmihel\router\response;
use fmihel\router\router;
use fmihel\router\input;
use fmihel\router\crypto;


require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__.'/../loader.php';
require_once __DIR__ . '/my-session.php';



// CORS: белый список или '*' задаётся в config.php
response::$corsOrigins     = config['cors'] ?? '*';
response::$corsCredentials = config['corsCredentials'] ?? false;

// Завершаем preflight-запрос (OPTIONS), если пришёл
response::preflight();

try {

    input::addUnpacker(function ($raw, array $envelope) {
        if (empty($envelope['enc'])) {
            return null;                       // не шифровано — не наше
        }
        if (! is_string($raw)) {
            return null;
        }

        $token = session::tokenFromRequest();  // работает ДО authorizeFromRequest()
        if (! $token) {
            throw new AccessDenied('encrypted payload without token');
        }

        $keypair = crypto::getKeypair(config['cryptoKey'], $token);
        $plain   = crypto::decrypt($raw, $keypair);

        $decoded = json_decode($plain, true);
        if (! is_array($decoded)) {
            throw new \Exception('bad encrypted payload');
        }
        return $decoded;
    });

    if (router::enable()) {

        // Авторизация из запроса: Bearer-заголовок или httpOnly-cookie (+ CSRF)
        session::authorizeFromRequest();

        $module = router::module([__DIR__]);
        if (! file_exists($module)) {
            throw new ModuleIsNotDefined($module);
        }
        require_once $module;

    }
} catch (AccessDenied $e) {
    response::error($e, response::HTTP_UNAUTHORIZED_401);
} catch (InvalidRoute $e) {
    response::error($e, response::HTTP_NOT_FOUND_404);
} catch (ModuleIsNotDefined $e) {
    response::error($e, response::HTTP_NOT_FOUND_404);
} catch (NotConfigured $e) {
    console::log($e);
    response::error($e, response::HTTP_INTERNAL_SERVER_ERROR_500);
} catch (\Exception $e) {

    console::log($e);
    response::error($e);
}
