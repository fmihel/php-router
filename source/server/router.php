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
    
    private $files = []; // список используемых файлов
    private $loadingFromCache = false;
    private $param = [
        'cache'   =>true,         // будет ли попытка загрузить классы из предварительно сохраненного списка файлов fileName
        'fileName'  =>'router.dat', // имя предварительно созданного файла со списком модулей
        'add'       =>[],           // список фалов или путей к подгрузке 
        'main'      =>'index.html',   // файл выгрузки, в случае если запрос не адресован к роутору
        'suspend'   =>true,         // если true то запуск будет через конструктор
    ];

    function __construct($param=[]){

        $this->REQUEST = $_REQUEST;

        $selfPath = dirname($_SERVER['SCRIPT_FILENAME']).'/';
        $this->param['fileName']    = $selfPath.$this->param['fileName']; // имя предварительно созданного файла
        $this->param['main']        = $selfPath.$this->param['main']; // файл выгрузки, в случае если запрос не адресован к роутору
        $this->param = array_merge($this->param,$param);

        if (!$this->param['suspend']) 
            return $this->handler();

        return true;
    }

    public function handler($param=[]){

        if ($this->isRouting()){
            $this->param = array_merge($this->param,$param);
            
            $load_ok = false;
            if ($this->param['cache'])
                $load_ok = $this->loadFromFile();
        
            foreach($this->param['add'] as $obj)
                $this->add($obj);
            
            $this->run();

            if ( (!$this->param['cache']) || (!$load_ok) )
                $this->saveToFile();

            return true;    
        };

        if (file_exists($this->param['main']))
            echo file_get_contents($this->param['main']);

        return false;

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
        if ($type==='object'){
            
            //error_log('['.__FILE__.':'.__LINE__.'] '.get_class($obj));
            $this->routers[] = ['object'=>$obj];

        }elseif(($type==='string') && (!$this->loadingFromCache)){
            $isFile = (!is_dir($obj));

            if ($isFile){
                $check = false;
                if (file_exists($obj)){

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
                                $this->files[] = $obj;
                                $this->routers[] = ['class'=>$cls];
                            };
                        }
                    };
                };
            }else{
                $files = DIR::files($obj,'php',true,($scan!=='depth'));
                foreach($files as $file)
                    $this->add($file,$test);
            }                
        }    
    }
    
    public function loadFromFile($fileName = false){
        try {
            if (!$fileName)
                $fileName = $this->param['fileName'];
        
            $this->files = [];
            $this->loadingFromCache = false;    
        
            if (file_exists($fileName)){

                $list = file_get_contents($fileName);
                if ($list === false)
                    throw new \Exception('file_get_contents("'.$fileName.'") = false');
                    
                $list = explode("\n",file_get_contents($fileName));
                foreach($list as $fileName)
                    $this->add($fileName);
                $this->loadingFromCache = true;    
                
                return true;
            }
        } catch (\Exception $e) {
            error_log('Execption ['.__FILE__.':'.__LINE__.'] '.$e->getMessage());
        }

        return false;
    }

    public function saveToFile($fileName = false){
        try {

            if (!$fileName)
                $fileName = $this->param['fileName'];

            return (file_put_contents($fileName,implode("\n",$this->files))!==false);
            
        } catch (\Exception $e) {
            error_log('Exception ['.__FILE__.':'.__LINE__.'] '.$e->getMessage());
        };

        return false;
    }
    /** проверка, на то что это запрос от router.js */
    public function isRouting(){
        return isset($this->REQUEST['fmihel_router_data']);
    }
    /**
     * обрабатываем входные данные
     */
    private function init(){
        try{
            
            $this->pack = $this->REQUEST['fmihel_router_data'];
        
        }catch(\Exception $e){
            error_log($e->getMessage());
            $this->pack = false;
        }
    }
    /**
     * @return false | ['object'=>obj,'method'=>'name']
     */
    private function getObject($route){
        
        if (isset($route['object'])){
            $object = $route['object'];
            $className = $object;
        }else if (isset($route['class'])){
            $object = false;
            $className = $route['class'];
        }
        
        if ($className){

            $eventMethods = [
                    strtoupper('ajax'.$this->pack['id']),
                    strtoupper('ajax_'.$this->pack['id']),
                    strtoupper('route'.$this->pack['id']),
                    strtoupper('route_'.$this->pack['id']),
            ];

            $methods = get_class_methods($className);
            $method = false;
            foreach($methods as $method){
                $method = strtoupper($method);
                $find = false;
                foreach($eventMethods as $ev){
                    if ($ev === $method){
                        $find = true;
                        break;
                    }
                }
                if ($find)
                    break;
                else
                    $method = false;
            }

            if (($method))
                return [
                        'object'=>$object?$object:(new $className),
                        'method'=>$method
                ];
        }
        return false;

    }
    /**
     * отсылаем запрос ко всем модулям
     */
    private function request(){
        try{
            if (!$this->pack)
                throw new \Exception('no data');
            
            for($i=0;$i<count($this->routers);$i++){
                
                $route = $this->getObject($this->routers[$i]);

                if ($route){
                    $object = $route['object'];
                    $method = $route['method'];

                    $object->pack    = $this->pack;
                    $object->id      = $this->pack['id'];
                    $object->data    = $this->pack['data'];
                    
                    if ($object->$method($this->pack['data'])){
                        $this->return = $object->return;
                        return true;
                    }

                }
            };

            $this->return = Route::typeError('No defined handler module for ['.$this->pack['id'].']');
    
        }catch(\Exception $e){
            
            error_log($e->getMessage());
            $this->return =  false; 
        }
        return false;
    }
    /**
     * возвращаем информацию
     */
    private function response(){
        
        $res = json_encode(array('pack'=>$this->return));
        echo $res;

    }
    public function run(){
        $this->init();
        $this->request();
        $this->response();
    }

}    

?>