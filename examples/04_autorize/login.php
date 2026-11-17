<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/my-session.php';

use fmihel\console;
use fmihel\router\exceptions\AccessDenied;
use fmihel\router\input;
use fmihel\router\response;

try {

    $login = input::data('login', false);
    $pass  = input::data('password', false);
    $token = input::data('token', false);

    console::log(input::data());

    if ($login && $pass) {
        session::autorizeByLogin($login, $pass);
    } elseif ($token) {
        session::autorizeByToken($token);
    } else {
        throw new AccessDenied();
    }

    response::success(session::clientTokenInfo());

} catch (AccessDenied $e) {
    response::error($e, 401);
} catch (\Exception $e) {
    console::log($e);
    response::error($e, 401);
}
