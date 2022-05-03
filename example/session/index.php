
<?php
require_once '..\..\source\server\router.php';

function before1(&$param){
    error_log('['.__FILE__.':'.__LINE__.'] '.'before:'.print_r($param,true));
    try{

        if ($param['session']['token'] != '1')
            throw new Exception('no autorize session!!', 0);
        $param['data'] = $param['data'].' addition info';

    } catch(\Exception $e) {
        error_log('['.__FILE__.':'.__LINE__.'] '.$e->getMessage());
        return $e->getMessage();
    }
    return true;
}

function after1(&$param){
    $param['session'] = ['enable'=>true];
    error_log('['.__FILE__.':'.__LINE__.'] '.'after1:'.print_r($param,true));
}
function after2(&$param){
    $param['data'] = $param['data'].' addition data from after';
    error_log('['.__FILE__.':'.__LINE__.'] '.'after2:'.print_r($param,true));
}


new \fmihel\router\Router([
    'add'       =>['modules/'],
    'suspend'   =>false,
    'cache'     =>true,
    'onBefore'  =>'before1',
    'onAfter'   =>['after1','after2']

]);

      
?>