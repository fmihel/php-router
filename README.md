# php-router
simple php router

---
## Install 

Server side

```composer require fmihel/php-router:dev-dev```

Client side

``` npm i fmihel-php-router-client@beta ```

---
## Simple use


create *index.js*
```js
import router from 'fmihel-php-router-client';

router::send({
    to:'path1/path2/mod',
    data:{ msg: 'send msg to server',any_num:10,arr:[1,32,4,2]},
})
    .then(data=>{
        console.info(data);
    })
    .catch(e=>{
        console.error(e);
    })

```

create *mod.php* in folder ```<index path>/path1/path2```
```php
<?php
    use fmihel\router;

    error_log(print_r(router::$data['msg']));

    router::out('hi, from server');
?>
```



create *index.php*
```php
<?php
require_once __DIR__.'/vendor/autoload.php';
use fmihel\router;

if (router::enable()){
    router::init();
    require_once router::module();
    router::done();
};

?>
```




