<?php
require_once __DIR__.'/../../source/router.php';
use fmihel\router;

if (router::enabled()){
    try{
        router::init([
            'root'=>__DIR__,
            'onBefore'=>function($data){ return $data; },
        ]);
        require_once router::module();
        router::done();
    
    }catch(\Exception $e){
        router::error($e);
    }

}else{
    echo file_get_contents(__DIR__.'/index.html');
}