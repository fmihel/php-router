<?php

$paths = [
    __DIR__.'/../server/utils',
    __DIR__.'/../server/exceptions/',
    __DIR__.'/../server',
];

foreach($paths as $path){
    $modules = glob($path . '/*.php');
    foreach($modules as $module){
        require_once $module;
    }
}
