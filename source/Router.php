<?php
namespace fmihel\router;

require_once __DIR__. '/Route.php';

use fmihel\lib\{Dir,Events};



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
    private $events = null;

    private $param = [
        'cache'     =>false,        // будет ли попытка загрузить классы из предварительно сохраненного списка файлов fileName
        'fileName'  =>'router_paths.php', // имя предварительно созданного файла со списком модулей
        'add'       =>[],           // список фалов или путей к подгрузке 
        'main'      =>'index.html', // файл выгрузки, в случае если запрос не адресован к роутору
        'suspend'   =>true,         // если true то запуск будет через конструктор
        
        'onBefore'  =>[],           // список событий сразу по приходу сообщения
        'onAfter'   =>[],           // список событий после обработки, сразу перед отправкой
        'onException'=>[],          // передача объекта Exception внутреннему обработчику, для внутренних целей ( к примеру для вывода в лог)
    ];

    function __construct($param=[]){

        $this->REQUEST = $_REQUEST;
        $this->events = new Events;

        $selfPath = dirname($_SERVER['SCRIPT_FILENAME']).'/';
        $this->param['fileName']    = $selfPath.$this->param['fileName']; // имя предварительно созданного файла
        $this->param['main']        = $selfPath.$this->param['main']; // файл выгрузки, в случае если запрос не адресован к роутору
        $this->param = array_merge($this->param,$param);
        
        $this->on(  'before', $this->param['onBefore']  );
        $this->on(  'after',  $this->param['onAfter']   );
        $this->on(  'exception',  $this->param['onException']   );

        if (!$this->param['suspend']) 
            return $this->handler();

        return true;
    }

    public function handler($param=[]){

        if ($this->isRouting()){
            $this->param = array_merge($this->param,$param);
            
            $load_ok = true;
            if ($this->param['cache'])
                $load_ok = $this->loadFromFile();
        
            foreach($this->param['add'] as $obj)
                $this->add($obj);
            
            $this->run();

            if ( ($this->param['cache']) && (!$load_ok) )
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
            $obj = trim($obj);
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
                $files = Dir::files($obj,'php',true,($scan!=='depth'));
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

                include $fileName;
                foreach($modules as $fileName)
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
            $php = '';
            $cr = "\n";
            foreach($this->files as $file){
                $php.="    '".$file."',".$cr;  
            };

            return (file_put_contents($fileName, '<?php'.$cr.'$modules=['.$cr.$php.'];'.$cr.'?>' )!==false);
            
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
     * регистрация события
    */
    public function on($event,$callback){
        if (gettype($callback) === 'array'){
            foreach($callback as $cb)
                $this->on($event,$cb);
        }else
            $this->events->add($event,$callback);
    }

    /** 
     * выполнение всех коллбеков для события event
    */
    private function do($event,&$params){
        return $this->events->do($event,$params);
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
    /** проверка, что имя класс принадлежит указанному пространству имен  */
    private function inNamespace($namespace,$className){
        
        return empty($namespace) || (strpos($className,$namespace)===0) ;
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
        
        if ($className && $this->inNamespace($this->pack['namespace'],$className)){

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

            // ----------------------------------------------------------------------------            
            //$pack = $this->pack;
            $evResult = $this->do('before',$this->pack);
            
            if ($evResult!==true){
                $typeResult = gettype($evResult);
                if ($typeResult === 'string')
                    $this->return = Route::typeError($evResult,0,null);
                else if ($typeResult === 'array')
                    $this->return = Route::typeError(
                        isset($evResult['msg'])?$evResult['msg']:'php:before ret false for '.$this->pack['id'],
                        isset($evResult['res'])?$evResult['res']:0,
                        isset($evResult['data'])?$evResult['data']:null
                    );
                else
                    $this->return = Route::typeError('php:before ret false for '.$this->pack['id'],0,null);

                /*
                $this->return = Route::typeError(
                    gettype($evResult)!=='string' ? 'php:before ret false for '.$this->pack['id'] : $evResult,
                    0,
                    gettype($evResult)!=='string'?$evResult:null
                );*/
                return;    
            }
            // ----------------------------------------------------------------------------            

            for($i=0;$i<count($this->routers);$i++){
                
                $route = $this->getObject($this->routers[$i]);

                if ($route){
                    $object = $route['object'];
                    $method = $route['method'];

                    $object->routeParam['pack']    = $this->pack;
                    $object->routeParam['id']      = $this->pack['id'];
                    /*
                    $object->routeParam['data']    = $this->pack['data'];
                    
                    if ($object->$method($this->pack['data'])){
                        $this->return = $object->routeParam['return'];
                        return;
                    }
                    */
                    $data = isset($this->pack['data'])?$this->pack['data']:null;
                    $object->routeParam['data']    = $data;

                    if ($object->$method($data)){
                        $this->return = $object->routeParam['return'];
                        return;
                    }


                }
            };

            $this->return = Route::typeError('No defined handler module for ['.$this->pack['id'].']');
    
        }catch(\Exception $e){
            $this->do('exception',$e);
            $this->return = Route::typeError($e->getMessage());            
        }
        return;
    }
    /**
     * возвращаем информацию
     */
    private function response(){
        
        try{
            // ----------------------------------------------------------------------------            
            $evResult = $this->do('after',$this->return);
            if ($evResult!==true){
                $this->return = Route::typeError(
                    gettype($evResult)!=='string' ? 'php:after ret false for '.$this->pack['id'] : $evResult,
                    0,
                    gettype($evResult)!=='string'?$evResult:null
                );
            }
            // ----------------------------------------------------------------------------            
            $res = json_encode(array('pack'=>$this->return));
            // ----------------------------------------------------------------------------            
            if (!$res)
                throw new \Exception('json_encode = false, use only utf8 coding for response messages');
            echo $res;

        }catch(\Exception $e){
            $this->do('exception',$e);
            echo json_encode( ['pack'=>Route::typeError($e->getMessage())]);            
        }
    }
    public function run(){
        $this->init();
        $this->request();
        $this->response();
    }

}    

?>