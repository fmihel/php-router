<?php
namespace fmihel\router;

class Route{
    public $routeParam = [
        'pack'      =>false,
        'return'    =>[],
        'id'        =>1,
        'data'      =>[]
    ];
    
    //public $pack = false;
    //public $return = array();
    //public $id      = 1;
    //public $data = array();
    
    public function result($data){
        $this->routeParam['return'] = $data;
        return true;
    }
    public function ok($data=[],$res=1){
        $this->routeParam['return'] = self::typeOk($data,$res);
        return true;
    }
    public function error($msg,$res=0,$data=[]){
        $this->routeParam['return'] = self::typeError($msg,$res,$data);
        return true;    
    }
    public function is($id){
        return ($id === $this->routeParam['id']);
    }
    public function request(){
        
    }

    static public function isError($res){
        return (($res) && (isset($res['res'])) && ($res['res']<=0));
    }

    static public function typeError($msg='',$res=0,$data=[]){
        $error = array('msg'=>$msg,'res'=>$res,'data'=>$data);
        return $error;
    }

    static public function typeOk($data=[],$res=1){
        return array('res'=>$res,'data'=>$data);
    }
    
}
?>