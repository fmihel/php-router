<?php
require_once __DIR__.'/../../source/router.php';

use fmihel\router;

function routeA($root,$path){
    if ($path === 'test'){
        return router::join($root,'/path/path2/test');
    }
}

if (router::enabled()){

    try{
        
        router::init([
            'root'=>__DIR__,
            'rules'=>[
                function ($root,$path){
                    if ($path === 'test'){
                        return router::join($root,'/path/path2/test');
                    }
                }
            ],    
        ]);
        

        require_once router::module();
        router::done();
    
    }catch(\Exception $e){
        router::error($e);
    }

}else{
?>

<!doctype html>
<html lang="en">
  <head>
    <title>simple</title>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script type='module' src="http://work/fmihel/router/php-router-client/source/router.js" ></script>
  </head>
  <body style="background: #000C18;color:gray">
    <button id="test">test</button>
    <script type="module">
       import router from 'http://work/fmihel/router/php-router-client/source/router.js';
    
        $(()=>{

            $('#test').on('click',()=>{
                router.send({
                    to:'test',
                    data:'from router'
                }).then(data=>{
                    console.log(data);
                })
            })


        });

    </script>
</body>
</html>

<?php
}