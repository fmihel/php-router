<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use fmihel\console;
use fmihel\router\input;
use fmihel\router\response;

$data = input::data('text');

console::log($data);

response::data(["return string ok"]);
