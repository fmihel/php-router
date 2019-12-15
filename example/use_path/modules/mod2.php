<?php

class Mod2 extends fmihel\router\Route{
    
    public function handler(){
        if ($this->is('test_send2')){
            
            if (true)
                return $this->ok("ok2");
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
