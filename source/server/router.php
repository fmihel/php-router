<?php
namespace fmihel\router;
use fmihel\router\lib\{DIR,ARR};


require_once __DIR__. '\route.php';
require_once __DIR__. '\lib\dir.php';
require_once __DIR__. '\lib\arr.php';

define('ROUTE_CLASS_NAME','fmihel\\router\\Route');

/**
 * Осуществляет распределение запросов, между отдельными модулями route
 */
final class Router{
    
    public $routers=array();
    public $pack = false;
    public $REQUEST;
    public $return = false;
    
    function __construct($gReq){
        
        $this->REQUEST = $gReq;
    }
    /**
     * добавляем модуль
     * @param string|object $obj - либо моудль Route, либо файл с классом , либо папка с файлами (от текущего папки "./")
     * @param string $test all | pretest    all - вставляет все найденные,pretest - предварительно проверяет есть ли класс Route внутри
     * @param string $scan depth | self  - глубина сканирования
     */
    public function add($obj,$test='pretest',$scan='depth'){
        //error_log('add:'.$obj);
        $type = gettype($obj);
        if ($type==='object')
            
            $this->routers[]=$obj;

        elseif($type==='string'){
            $isFile = (!is_dir($obj));

            if ($isFile){
                $check = false;

                if ($test==='pretest'){
                    $cont = file_get_contents($obj);
                    $re = '/class\s+[\s\S]+extends\s+\S*Route/m';
                    $check = (preg_match($re,$cont)===1);
                };
                
                if (($test==='all')||($check)){
                    
                    $prev = get_declared_classes();
                    include_once ($obj);
                    $classes = array_diff(get_declared_classes(), $prev);
                    foreach($classes as $cls){
                        if (is_subclass_of($cls,ROUTE_CLASS_NAME)){
                            $this->add(new $cls);
                        };
                    }
                };
            }else{
                $files = DIR::files($obj,'php',true,($scan!=='depth'));
                foreach($files as $file)
                    $this->add($file,$test);
            }                
        }    
    }
    
    public function loadFromFile($fileName){
        // need release
    }

    public function saveToFile($fileName){
        // need release
    }
    /** проверка, на то что это запрос от router.js */
    public function isRouting(){
        return isset($this->REQUEST['fmihel_router_data']);
    }
    /**
     * обрабатываем входные данные
     */
    public function init(){
        try{
            
            $this->pack = $this->REQUEST['fmihel_router_data'];
        
        }catch(\Exception $e){
            error_log($e->getMessage());
            $this->pack = false;
        }
    }


    /**
     * отсылаем запрос ко всем модулям
     */
    public function request(){
        try{
            if (!$this->pack)
                throw new \Exception('no data');
            
            //error_log('AAA TEST');

            for($i=0;$i<count($this->routers);$i++){
                $route = $this->routers[$i];
                $route->pack = $this->pack;
                $route->id = $this->pack['id'];
                $route->data = $this->pack['data'];

                if ($route->request()===true){
                    $this->return = $route->return;
                    return true;
                }    
            };

            $this->return = Route::typeError('No defined handler module for ');
    
        }catch(\Exception $e){
            
            error_log($e->getMessage());
            $this->return =  false; 
        }
        return false;
    }
    /**
     * возвращаем информацию
     */
    public function response(){
        
        $res = json_encode(array('pack'=>$this->return));
        echo $res;

    }
    public function run(){
        $this->init();
        $this->request();
        $this->response();
    }

}    

$router = new Router($_REQUEST);


?>