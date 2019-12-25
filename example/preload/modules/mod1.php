<?php

class Mod1 extends fmihel\router\Route{
    
    public function route_test_send1(){
        if (true)
            return $this->ok("ok1");
        else    
            return $this->error('Missing data..');
    }    
    
}
?>
