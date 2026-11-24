<?php

require_once __DIR__ . '/../../vendor/autoload.php';
use fmihel\console;
use fmihel\router\input;
use fmihel\router\response;

try {

    console::log(input::data());
    response::success();

} catch (\Throwable $th) {
    response::error();
};
