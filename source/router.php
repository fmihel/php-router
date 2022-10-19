<?php

namespace fmihel;

/* ----------------------------------------------
Example for use:
//-----------------------------------------------
require_once __DIR__.'/router.php';
if (router::enabled()){
    try{
        router::init([
            'root'=>__DIR__,
            'onBefore'=>function($data){ return $data; },
        ]);
        require_once router::module();
        router::done();
    
    }catch(\Exception $e){
        router::error($e);
    }
};
//-----------------------------------------------
*/
class router {
    
    static private $enable = null;
    static private $id = 'router';
    
    static private $pack     = null;
    static public $data     = [];
    static public $path     = '';    

    static private $events = ['after'=>[],'before'=>[]];
    static private $root = __DIR__;
    

    public static function init($params=[]){

        if (self::_tryLoad()){
           
            $params = array_merge([
                'root'      =>__DIR__,
                'before'  =>false,
                'after'   =>false,
            ],$params);

            self::$data       = self::$pack['data'];
            self::$path       = self::$pack['to'];
            

            self::$root       = $params['root'];
        
            if ($params['after'])
                self::on('after',$params['after']);

            if ($params['before'])
                self::on('before',$params['before']);

            return true;
        };
        
        return false;
    }
    
    public static function module(){
        
        self::$pack = self::doEvent('before',self::$pack);
        $module_name = self::$root.'/'.self::$path.'.php';
        if (!file_exists($module_name))
            self::error('module not exist '.$module_name);
        return $module_name;

        
    }
    
    public static function done($data=[]){
        self::out($data);
    }
    
    public static function error($e){
        
        $msg = is_object($e) ? $e->getMessage() : $e ;
        echo json_encode([ 'res'=>0 , 'msg' => $msg ]);         
        exit;
    }

    public static function out($data){
        self::$pack['data'] = $data;
        self::$pack = self::doEvent('after',self::$pack);
        echo json_encode(array_merge([ 'res'=>1 ],self::$pack));         
        exit;
    }
    
    public static function on($ev,$callback){
        if (!array_key_exists($ev,self::$events))
            throw new \Exception("event ".$ev.' is not event of router ');
            
        self::$events[$ev][] = $callback;
        
    }
    
    private static function doEvent($ev,$pack){
        if (!array_key_exists($ev,self::$events))
            throw new \Exception("event ".$ev.' is not event of router ');
            
        foreach(self::$events[$ev] as $callback){
                $pack = $callback($pack);
        };
        return $pack;
    }
    private static function _tryLoad(){
        
        if (self::$enable === null){
            $input  = json_decode(trim(file_get_contents("php://input")),true);
            if ($input && isset($input[self::$id])){
                self::$enable = true;
                self::$pack = $input[self::$id];
            };
        };

        return self::$enable;
    }

    public static function enabled(){
        if (self::$enable === null){
            self::_tryLoad();
        }
        return self::$enable;
    }
    
    
}
