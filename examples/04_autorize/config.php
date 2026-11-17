<?php
// require_once 'vendor/autoload.php';

// // Секретный ключ (должен быть очень сложным)
// define('JWT_SECRET', 'YOUR_SUPER_SECRET_KEY_123456');

// // Идентификатор вашего сервера/сайта
// define('JWT_ISSUER', 'my-api-server.local');

// // Время жизни токена (1 час = 3600 секунд)
// define('JWT_EXPIRE', 3600);
define('CONFIG_EXEPTION', '-exception-');
define('config', [
    'jwt' => [
        'key'    => 'wefhg-23434-werf83hjjh-gwerf2', // Секретный ключ (должен быть очень сложным)
        'server' => 'local',                         // Секретный ключ (должен быть очень сложным)
        'expire' => 366,                             // кол-во дней жизни
    ],

]);
