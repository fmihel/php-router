<?php
require_once __DIR__.'/../../source/router.php';
use fmihel\router;

function onBefore($pack){
    error_log(print_r($pack,true));
    return $pack;
}


if (router::enabled()){
    try{
        router::on('before','onBefore');

        router::init([
            'root'=>__DIR__,
        ]);
        require_once router::module();
        router::done();
    
    }catch(\Exception $e){
        router::error($e);
    }

}else{
    echo file_get_contents(__DIR__.'/index.html');
}