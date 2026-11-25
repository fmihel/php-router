<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/my-session.php';

use fmihel\console;
use fmihel\router\exceptions\AccessDenied;
use fmihel\router\exceptions\NotConfigured;
use fmihel\router\input;
use fmihel\router\response;

$cryptoKey = config['router']['crypto']['key'];

// CORS: белый список или '*' задаётся в config.php
response::$corsOrigins     = config['router']['session']['cors']['origin'] ?? '*';
response::$corsCredentials = config['router']['session']['cors']['credentials'] ?? false;

// Завершаем preflight-запрос (OPTIONS), если пришёл
response::preflight();

try {

    // Серверный logout: в cookie-режиме удаляет httpOnly-cookie
    if (input::data('action', false) === 'logout') {
        session::logout();
        response::success();
    }

    $login = input::data('login', false);
    $pass  = input::data('password', false);
    $token = input::data('token', false);

    if ($login && $pass) {
        session::authorizeByLogin($login, $pass);
    } elseif ($token) {
        session::authorizeByToken($token);
    } else {
        // cookie-режим: токен приходит в httpOnly-cookie (или Bearer-заголовке)
        session::authorizeFromRequest();
    }

    response::success(session::clientTokenInfo($cryptoKey));

} catch (AccessDenied $e) {
    response::error(response::HTTP_UNAUTHORIZED_401);
} catch (NotConfigured $e) {
    console::log($e);
    response::error(response::HTTP_UNAUTHORIZED_401);
} catch (\Exception $e) {
    console::log($e);
    response::error(response::HTTP_UNAUTHORIZED_401);
}
