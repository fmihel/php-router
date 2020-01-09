<?php
namespace fmihel\router\lib;

use Exception;

class Arr {
    public static function is_assoc($array){
        $result = false;
        try{
        
            $result  = ( count(array_filter(array_keys($array), 'is_string')) > 0 );
        
        }catch(\Exception $e){
                
        }
        return $result;
    }
    
    public static function extend($a = array(), $b = array()){
        
        if ((is_array($a)) && (is_array($b))) {
            $res = array();
            if (self::is_assoc($a)) {
                foreach ($a as $k => $v) {
                    if (!isset($b[$k])) {
                        $res[$k] = $v;
                    } else {
                        if ((is_array($v)) && (is_array($b[$k]))) {
                            $res[$k] = self::extend($v, $b[$k]);
                        } else {
                            $res[$k] = $b[$k];
                        }
    
                    }
                }
            }
            return $res;
        };
        return $a;
    }
    

}


?>