<?php

use fmihel\console;
use fmihel\router\input;
use fmihel\router\response;

$data  = input::data();
$attrs = input::attrs();
console::log($data, $attrs);

response::ok();
