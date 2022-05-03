<?php

class Mod1 extends fmihel\router\Route{
    
    public function route_test_send1($data){
        error_log('['.__FILE__.':'.__LINE__.'] '.'mod1:'.print_r($data,true));
        return $this->ok("ok1");
    }    
    
}
?>
