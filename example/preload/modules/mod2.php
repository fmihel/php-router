<?php

class Mod2 extends fmihel\router\Route{
    
    public function route_test_send2(){
        if (true)
            return $this->ok("ok2");
        else    
            return $this->error('Missing data..');
    }    
    
}
?>
