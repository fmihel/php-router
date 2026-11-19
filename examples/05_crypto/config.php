<?php
define('CONFIG_EXCEPTION', '-exception-');
define('config', [
    'jwt'               => [  
        'key'    => 'wefhg-23434-werf83hjjh-gwerf27jjakskdlsl', // Секретный ключ (должен быть очень сложным)
        'server' => 'local',                         // Issuer: для какого сервера выпущен токен
        'expire' => 366,                             // кол-во дней жизни (кладётся в claim exp)
    ],

    // Способ хранения токена на клиенте:
    //   'header' — Bearer-токен (клиент хранит сам, напр. localStorage);
    //   'cookie' — httpOnly-cookie (защита от XSS) + CSRF double-submit.
    // 'tokenMode'       => 'header',
    'tokenMode'         => 'cookie',

    // CORS: '*' — разрешить любые origin, либо массив белого списка
    // 'cors' => '*',
    'cors'              => ['http://localhost', 'http://127.0.0.1'],

    // Разрешать cookie/credentials в кросс-доменных запросах
    'corsCredentials'   => true,
    'cryptoKey'         =>'qwejdgjh-qwedh-qwedhg-qwedqwe'
]);
