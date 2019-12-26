# php-router
simple php router

---
## Install 

Server side

```composer require fmihel/php-router```

Client side

``` npm i fmihel-php-router-client ```

---
## Simple use


create *index.js*
```js
import router from 'fmihel-php-router-client';

router({
    id:'MSG_SEND',
    data:{ msg: 'send msg to server',any_num:10,arr:[1,32,4,2]},
})
.then(data=>{
    console.info(data);
})
.catch(e=>{
    console.error(e);
})

```

create *mod1.php*
```php
<?php

class Mod1 extends fmihel\router\Route{
    
    public function route_MSG_SEND($from){
        error_log(print_r($from,true));
        return $this->ok("send Ok on response MSG_SEND");
    }    
    
}
?>
```



create *index.php*
```php
<?php
require_once __DIR__.'/vendor/autoload.php';

new \fmihel\router\Router([
    'add'       =>['mod1.php'],
    'suspend'   =>false,
]);

?>
```




