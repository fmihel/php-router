<?php
use fmihel\iRouterRule;

class MyRule implements iRouterRule{
    public function adapt($root,$path){
        if ($path === 'test'){
            return $root.'/path/path2/test.php';
        }
    }
}
