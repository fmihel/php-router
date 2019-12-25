<?php
    require_once '..\..\source\server\router.php';
    
    $router = new \fmihel\router\Router();
    $router->add('mod1.php');
    $router->run();
?>
