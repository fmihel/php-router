<?php

namespace fmihel\router;

class router {
    
    static public $all     = false;
    static public $data     = [];
    static public $path  = '';    
    static private $events = ['onAfter'=>[],'onBefore'=>[]];
    static private $root = __DIR__;
    
    public static function init($params=[]){
        $params = array_merge([
            'root'      =>__DIR__,
            'onBefore'  =>false,
            'onAfter'   =>false,
        ],$params);

        self::$all        = json_decode(trim(file_get_contents("php://input")),true);
        self::$data       = self::$all['data'];
        self::$path       = self::$all['to'];
        self::$root       = $params['root'];
        
        if ($params['onAfter'])
            self::addEvent('onAfter',$params['onAfter']);

        if ($params['onBefore'])
            self::addEvent('onBefore',$params['onBefore']);
        
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
    
    
}
