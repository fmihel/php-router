<?php
require_once __DIR__.'/../../source/router.php';
require_once __DIR__.'/MyRule.php';

use fmihel\router;

if (router::enabled()){

    try{
        
        router::init([
            'root'=>__DIR__,
            'rules'=>[ new MyRule() ],    
        ]);
        

        require_once router::module();
        router::done();
    
    }catch(\Exception $e){
        router::error($e);
    }

}else{
    echo file_get_contents(__DIR__.'/index.html');
}