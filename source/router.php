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
    static private $root = '';

    static private $rules = []; // [ 'from/path'=>'to/path',callback($root,$path):string || undef  ]

    public static function init($params=[]){

        if (self::_tryLoad()){
           
            $params = array_merge([
                'root'      =>pathinfo($_SERVER['SCRIPT_FILENAME'])['dirname'], 
                'before'    =>false,
                'after'     =>false,
                'rules'     =>[]
            ],$params);

            self::$data       = self::$pack['data'];
            self::$path       = self::$pack['to'];
            

            self::$root       = $params['root'];
        
            if ($params['after'])
                self::on('after',$params['after']);

            if ($params['before'])
                self::on('before',$params['before']);

            self::$rules  = array_merge(self::$rules,$params['rules']);

            return true;
        };
        
        return false;
    }

    public static function done($data=[]){
        self::out($data);
    }
    

    /** возвращает имя модуля к которому идет обращение от клиента */
    public static function module(){
        
        self::$pack = self::doEvent('before',self::$pack);
        
        $module_name = self::calcModuleName(self::$rules,self::$root,self::$path);
        
        if (!file_exists($module_name))
            self::error('module not exist '.$module_name);

        return $module_name;
        
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
    /** возвращает признак, что в скрипт передана информация для роутинга */
    public static function enabled():bool{
        if (self::$enable === null){
            self::_tryLoad();
        }
        return (self::$enable===true);
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

    /** расчет имени маршрута с учетом добавленных пользователей */
    private static function calcModuleName($rules,$root,$path){
        
        $find = false;
        for($i = 0;$i<count($rules);$i++){
            
            $rule = $rules[$i];
            $type = gettype($rule);

            if ($type === 'array'){ 
                foreach($rule as $from=>$to){
                    if ($path === $from){
                        $find = $to;
                        break;
                     };
                };
                
            }elseif($type === 'string' || $type === 'object'){
                $find = $rule($root,$path);
            };
            
            if ($find) break;
        };


        return self::addPhpExt($find ? $find : self::join($root,$path));
    }

    
    public static function addRule($rule){
        self::$rules[] = $rule;
    }
    /** конкатенация имен маршрутов */
    public static function join(...$paths){
        
        foreach($paths as $key=>$path){
            $paths[$key] = str_replace('/','\\',$path);
        };
        
        $root = ':\\';
        $rootKey = '<%ROOT%>';
        $out = str_replace($root,$rootKey,join('/',$paths));
        
        $out = explode('/',$out);
        $out = array_filter($out, function($value) { return !is_null($value) && $value !== ''; });
        
        $other = [];
        foreach($out as $a){
            $a = explode('\\',$a);
            $other = array_merge($other,array_filter($a, function($value) { return !is_null($value) && $value !== ''; }));
        }
        $out = $other;

        $out = join('/',$out);
        $out = str_replace($rootKey,$root,$out);
        
        if (strpos($out,$root)===false){
            $out='/'.$out;
        }
        return $out;
    }
    /** добавляет расширение php */
    private static function addPhpExt($path){
        $path = trim($path);
        $pos = strrpos(strtolower($path),'.php');
        return ($pos === strlen($path)-4) ? $path : $path.'.php';    
    }
    
}
