<?php
namespace fmihel;
error_log('autorize.php');
if ( session::autorizeByPass(router::$data['pass']) ){
    router::out(['enable'=>1,'id'=>session::$user['id']]);
}else{
    router::out(['enable'=>0]);
}
