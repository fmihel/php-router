
<?php
require_once '..\..\source\server\router.php';

new \fmihel\router\Router([
    'add'       =>['modules/'],
    'suspend'   =>false,
    'cache'     =>true,
]);

      
?>