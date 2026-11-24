<?php

use fmihel\console;
use fmihel\router\input;
use fmihel\router\response;

console::log(input::data());

response::ok();
