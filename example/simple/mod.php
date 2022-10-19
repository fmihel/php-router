<?php
use fmihel\router;

if (router::$data === 'error'){
    throw new \Exception('bebeb');
}else
    error_log(print_r(router::$data,true));
    
router::out([]);