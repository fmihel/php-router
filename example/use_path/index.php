
<?php
require_once '..\..\source\server\router.php';

$router = new \fmihel\router\Router();

if ($router->isRouting()){
    
    $router->add('modules/');
    $router->run();

}else{
?>        
<!doctype html>

<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <title>router</title>
    <style>
        body{
            background: #000C18;
            color:gray;
        }    
    </style>
  </head>
  <body>
    <div class="container-fluid bg-dark">
        <div class="row">
            <div class="col" style="min-height:48px;padding:10px">
                <button id="send1">send1</button>
                <button id="send2">send2</button>
            </div>
        </div>
    </div>    

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script type="module" src="../../source/client/router.js" ></script>
    <script type="module">
       import {router} from '../../source/client/router.js';
    
    $(()=>{

        $('#send1').on("click",()=>{
            
            router({
                id:'test_send1',
                data:"test data",

            })
            .then(o=>{
                console.log(o);
            })
            .catch(e=>{
                console.error(e);
            });
        });

        $('#send2').on("click",()=>{
            
            router({
                id:'test_send2',
                data:"test data",

            })
            .then(o=>{
                console.log(o);
            })
            .catch(e=>{
                console.error(e);
            });
        });

    });
    
    
    </script>
  
</body>
</html>

<?php }; ?>