<?php

class Mod3 extends fmihel\router\Route{
    
    public function route_test_send3($data){
        
        error_log('['.__FILE__.':'.__LINE__.'] '.print_r($data,true));

        if (true)
            return $this->ok("ok3");
        else    
            return $this->error('Missing data..');
    }    
    
}
?>
