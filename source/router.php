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
    
    static private $all     = null;
    static public $data     = [];
    static public $path  = '';    
    static private $events = ['onAfter'=>[],'onBefore'=>[]];
    static private $root = __DIR__;
    

    public static function init($params=[]){

        if (self::_tryLoad()){
           
            $params = array_merge([
                'root'      =>__DIR__,
                'onBefore'  =>false,
                'onAfter'   =>false,
            ],$params);

            self::$data       = self::$all[self::$id]['data'];
            self::$path       = self::$all[self::$id]['to'];
            self::$root       = $params['root'];
        
            if ($params['onAfter'])
                self::addEvent('onAfter',$params['onAfter']);

            if ($params['onBefore'])
                self::addEvent('onBefore',$params['onBefore']);

            return true;
        };
        
        return false;
    }
    
    public static function module(){
        
        self::$data = self::doEvent('onBefore',self::$data);

        return self::$root.'/'.self::$path.'.php';
        
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
        $data = self::doEvent('onAfter',$data);
        echo json_encode([ 'res'=>1 , 'data' => $data ]);         
        exit;
    }
    
    public static function addEvent($ev,$callback){
        if (!array_key_exists($ev,self::$events))
            throw new \Exception("event ".$ev.' is not event of router ');
            
        self::$events[$ev][] = $callback;
        
    }
    
    private static function doEvent($ev,$data){
        if (!array_key_exists($ev,self::$events))
            throw new \Exception("event ".$ev.' is not event of router ');
            
        foreach(self::$events[$ev] as $callback){
                $data = $callback($data);
        };
        return $data;
    }
    private static function _tryLoad(){
        
        if (self::$enable === null){
            self::$all  = json_decode(trim(file_get_contents("php://input")),true);
            self::$enable = self::$all && isset(self::$all[self::$id]);
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
