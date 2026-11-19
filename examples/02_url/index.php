<?php

use fmihel\router\response;
use fmihel\router\router;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__.'/../loader.php';

response::html(__DIR__ . '/index.html');
