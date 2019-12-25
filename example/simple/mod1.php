<?php
namespace bob\marl;

class Mod1 extends \fmihel\router\Route{
    
    public function ajaxTEST_SEND($data){
        error_log('['.__FILE__.':'.__LINE__.'] '.'data:'.print_r($data,true));
        if (true)
            return $this->ok("ok");
        else    
            return $this->error('Missing data..');
    }    

}
?>
