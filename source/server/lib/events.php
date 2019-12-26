<?php
namespace fmihel\router\lib;
/**
 * класс коллекция событий и их обработчиков
 */
class Events{

    private $list=[];
    /**
     * добавление события
     * 
     * Ex: если просто ф-ция
     * function mm(){..}
     * Events::add('onComplete',__NAMESPACE__.'\mm');
     * 
     * Ex: если метод
     * $t->func();
     * Events::add('onComplete',[$t,'func']);
     *  
     * obj::ff();
     * Events::add('onComplete',__NAMESPACE__.'\obj::ff');
     */
    public function add(string $event,$func){
        
        if ( isset($this->list[$event]) && ( array_search($func,$this->list[$event]) !== false ) ) // если уже есть
            return;

        $this->list[$event][] = $func;
    }
    /**
     * вызов всех ф-ций привязаных к событию
     * Ex:
     * Events::do('doComplete');
     * Events::do('doComplete',['sender'=>$this,'key'=>1]);
     * 
     */
    public function do(string $event,&$params){

        if (!isset($this->list[$event])) 
            return true;

        $funcs = $this->list[$event];
        foreach($funcs as $func){
            try{
                $res = $func($params);
                if ( ! (($res === null ) || ($res === true)) )
                    return $res;
                
            }catch(\Exception $e){
                error_log('error call ['.$func.'] '.$e->getMessage());
                return false;
            }
        }
        
        return true;

    }
}


?>