<?php

class Mod1 extends fmihel\router\Route{
    
    public function handler(){
        if ($this->is('test_send1')){
            
            if (true)
                return $this->ok("ok1");
            else    
                return $this->error('Missing data..');
        }
    }    
    
    public function request(){
        if ($this->handler()) return true;
        return false;
    }    
}
?>
