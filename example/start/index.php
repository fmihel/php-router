<?php

require_once __DIR__.'/vendor/autoload.php';
use fmihel\router\Router;
/** 
 * on local include .htaccess for fix CORS
*/
new Router([
    'cache'=>false,
    'add'=>['mods/'],
    'suspend'=>false
]);

?>
