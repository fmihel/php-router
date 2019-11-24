<?php
namespace fmihel\router;

class Route{
    public $pack = false;
    public $return = array();
    public $id      = 1;
    public $data = array();
    
    public function result($data){
        $this->return = $data;
        return true;
    }
    public function ok($data=array(),$res=1){
        $this->return = self::typeOk($data,$res);
        return true;
    }
    public function error($msg,$res=0,$data=array()){
        $this->return = self::typeError($msg,$res,$data);
        return true;    
    }
    public function is($id){
        return ($id === $this->id);
    }
    public function request(){
        
    }

    static public function typeError($msg='',$res=0,$data=array()){
        $error = array('msg'=>$msg,'res'=>$res);
        if ($data!==array())
            $error=$data;
        return $error;
    }

    static public function typeOk($data=array(),$res=1){
        return array('res'=>$res,'data'=>$data);
    }

    
}
?>