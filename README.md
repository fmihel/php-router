# php-router v5

Простой клиент-серверный роутер для PHP + JavaScript.

Маршрут приходит от клиента (поле `to`), сервер по нему подключает нужный PHP-модуль.
В комплекте: разбор запроса, JSON-ответы с CORS, JWT-авторизация (Bearer / httpOnly-cookie)
и опциональное шифрование тела запроса (libsodium sealed box).

---

## 1. Краткое описание

| Слой    | Файлы                                              | Назначение                                                        |
| ------- | -------------------------------------------------- | ----------------------------------------------------------------- |
| Сервер  | [`server/router.php`](server/router.php)           | Собирает путь к модулю по маршруту, защищает от path traversal     |
|   | [`server/input.php`](server/input.php)             | Читает `route` и `data` из тела запроса, поддержка распаковки      |
|   | [`server/response.php`](server/response.php)       | JSON-ответы (`success`/`error`/`data`), CORS, preflight, HTML      |
|   | [`server/session.php`](server/session.php)         | JWT-авторизация: Bearer-токен или httpOnly-cookie + CSRF           |
|   | [`server/crypto.php`](server/crypto.php)           | Ключевая пара и расшифровка (libsodium sealed box)                 |
| Клиент  | [`client/Request.js`](client/Request.js)           | `fetch`-обёртка `GET`/`POST` с перехватчиками                     |
|   | [`client/Session.js`](client/Session.js)           | Авторизация, хранение токена, logout, события                     |
|   | [`client/Crypto.js`](client/Crypto.js)             | Шифрование исходящих данных публичным ключом сервера              |

Формат обмена (POST):

```json
{ "to": "ajax/handler", "data": { "text": "hello" } }
```

Клиент передаёт маршрут в `to`, полезную нагрузку в `data`. Сервер отвечает JSON:
`{ "success": true, "payload": ... }` либо `{ "success": false, "message": ... }`.

---

## 2. Установка

### Серверная часть (PHP)

```bash
composer require fmihel/php-router
```

Требуется PHP 7.4+ с расширением `sodium` (для режима шифрования) и
[`firebase/php-jwt`](https://github.com/firebase/php-jwt) (ставится автоматически как зависимость).

> Примечание: версия `firebase/php-jwt`, зафиксированная в [`composer.json`](composer.json) (`^7.2`),
> требует PHP 8.0. Для работы на PHP 7.4 разрешите совместимую ветку библиотеки
> (например, `^5.5 || ^6.0`).

### Клиентская часть (JS)

Клиентские модули лежат в каталоге `client/` и подключаются напрямую как ES-модули.
Для режима шифрования нужен пакет `libsodium-wrappers`:

```bash
npm install libsodium-wrappers
```

Либо подключите его через import map из CDN (см. пример в [`examples/05_crypto/index.html`](examples/05_crypto/index.html)).

### Запуск примеров

```bash
php -S localhost:8000
```

Затем откройте в браузере [`examples.html`](examples.html).

---

## 3. Примеры использования

Далее для каждого сценария показаны серверная и клиентская части.
Полные рабочие версии — в каталоге [`examples/`](examples/).

### 3.1 Пример простого роутинга

**Серверная часть**

Точка входа `index.php` принимает запрос, находит модуль по маршруту и подключает его.

```php
<?php
use fmihel\router\response;
use fmihel\router\router;

require_once __DIR__ . '/vendor/autoload.php';

response::preflight(); // завершает OPTIONS-запрос и выставляет CORS

try {
    if (router::enable()) {
        $module = router::module([__DIR__]); // базовый каталог для модулей
        require_once $module;
    }
} catch (\Throwable $th) {
    response::error();
}
```

Модуль-обработчик `test.php` читает данные и отвечает:

```php
<?php
use fmihel\router\input;
use fmihel\router\response;

$data = input::data('text'); // поле data.text из тела запроса

response::ok();               // { "success": true, "payload": "" }
```

**Клиентская часть**

```html
<script type="module">
    import { Request } from './client/Request.js';

    const request = new Request();

    request
        .send({
            to: 'test',             // маршрут -> test.php
            data: { text: 'test' }, // полезная нагрузка
        })
        .then((o) => console.log(o.data));
</script>
```

- `Request.send()` по умолчанию шлёт `POST` на `./index.php` (поле `this.router`).
- Маршрут безупречно безопасен: [`router::module()`](server/router.php:29) запрещает
  абсолютные пути и `..`, ограничивает набор символов и проверяет, что итог лежит внутри базового каталога.
- Если файл модуля не найден — [`ModuleIsNotDefined`](server/exceptions/ModuleIsNotDefined.php),
  его можно поймать и вернуть 404 (см. [`examples/03_routing/index.php`](examples/03_routing/index.php)).

### 3.2 Пример роутинга с авторизацией

Авторизация встроена в [`server/session.php`](server/session.php) и опирается на JWT.
Поддерживаются два режима хранения токена:

- `header` — Bearer-токен, клиент хранит его сам (localStorage / sessionStorage);
- `cookie` — httpOnly-cookie (защита от XSS) + CSRF double-submit для изменяющих методов.

> Режим задаётся **синхронно** на сервере (`session::$tokenMode`) и на клиенте (`new Session(..., { mode })`).

**Серверная часть**

Наследуем [`session`](server/session.php) и реализуем `checkLogin` и `data`.
Секретный ключ, issuer и срок жизни выносим в конфиг.

```php
<?php
// config.php
define('config', [
    'jwt' => [
        'key'    => 'very-long-random-secret', // ОБЯЗАТЕЛЬНО заменить
        'server' => 'local',                   // issuer
        'expire' => 366,                       // время жизни в днях
    ],
    'tokenMode'       => 'cookie',             // 'header' | 'cookie'
    'cors'            => ['http://localhost'],  // белый список origin
    'corsCredentials' => true,                 // для cookie-режима
]);
```

```php
<?php
// my-session.php
use fmihel\router\exceptions\AccessDenied;

require_once __DIR__ . '/config.php';

define('USERS', [
    ['id' => 1, 'login' => '1', 'password' => '1', 'name' => 'mike', 'right' => 'admin'],
]);

class session extends fmihel\router\session
{
    // вызывается при логине; возвращает данные, которые попадут в токен
    public static function checkLogin(string $login, string $password): array
    {
        foreach (USERS as $user) {
            if ($user['login'] === $login && $user['password'] === $password) {
                return ['id' => $user['id'], 'name' => $user['name']];
            }
        }
        throw new AccessDenied();
    }

    // доступ к полям пользователя из payload токена
    protected static function data(string $name, $default = self::EXCEPTION)
    {
        foreach (USERS as $user) {
            if ($user['id'] === self::$tokenData['id']) {
                return $user[$name] ?? $default;
            }
        }
        throw new \Exception('not define ' . $name);
    }

    // данные, отправляемые клиенту вместе с токеном
    public static function clientTokenInfo(): array
    {
        return [
            'token' => self::tokenForClient(), // в cookie-режиме вернёт null
            'name'  => self::$tokenData['name'],
            'right' => self::data('right'),
        ];
    }
}

session::$key       = config['jwt']['key'];
session::$server    = config['jwt']['server'];
session::$expire    = config['jwt']['expire'];
session::$tokenMode = config['tokenMode'] ?? 'header';
```

Точка входа защищает весь роутинг: перед подключением модуля вызывается
[`session::authorizeFromRequest()`](server/session.php:131).

```php
<?php
// index.php
use fmihel\router\response;
use fmihel\router\router;
use fmihel\router\exceptions\AccessDenied;
use fmihel\router\exceptions\ModuleIsNotDefined;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/my-session.php';

response::$corsOrigins     = config['cors'] ?? '*';
response::$corsCredentials = config['corsCredentials'] ?? false;
response::preflight();

try {
    if (router::enable()) {

        session::authorizeFromRequest(); // 401, если нет валидного токена

        $module = router::module([__DIR__]);
        if (! file_exists($module)) {
            throw new ModuleIsNotDefined($module);
        }
        require_once $module;
    }
} catch (AccessDenied $e) {
    response::error(response::HTTP_UNAUTHORIZED_401);
} catch (ModuleIsNotDefined $e) {
    response::error(response::HTTP_NOT_FOUND_404);
} catch (\Exception $e) {
    response::error(response::HTTP_INTERNAL_SERVER_ERROR_500);
}
```

Отдельный скрипт `login.php` обслуживает вход, авто-вход по токену и серверный logout:

```php
<?php
// login.php
use fmihel\router\input;
use fmihel\router\response;
use fmihel\router\exceptions\AccessDenied;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/my-session.php';

response::preflight();

try {
    if (input::data('action', false) === 'logout') { // серверный logout (удаляет cookie)
        session::logout();
        response::success();
    }

    $login = input::data('login', false);
    $pass  = input::data('password', false);
    $token = input::data('token', false);

    if ($login && $pass) {
        session::authorizeByLogin($login, $pass);   // вход по логин/паролю
    } elseif ($token) {
        session::authorizeByToken($token);          // вход по токену (localStorage)
    } else {
        session::authorizeFromRequest();            // cookie-режим
    }

    response::success(session::clientTokenInfo());
} catch (AccessDenied $e) {
    response::error(response::HTTP_UNAUTHORIZED_401);
}
```

**Клиентская часть**

[`Session`](client/Session.js) сам добавляет заголовок `Authorization: Bearer ...`
(в local-режиме) или CSRF-заголовок (в cookie-режиме) через перехватчик в [`Request`](client/Request.js).

```html
<script type="module">
    import { Request } from './client/Request.js';
    import { Session } from './client/Session.js';

    const request = new Request();
    const session = new Session(request, {
        mode: 'cookie',    // 'local' | 'cookie' — должно совпадать с config.php
        storage: 'local',  // 'local' | 'session' (только для mode: 'local')
    });

    // Вход по логину/паролю
    await session.authorizeByLogin('1', '1');

    // Авто-вход по сохранённому токену (при загрузке страницы)
    session.authorizeByToken().catch(() => {});

    // Защищённый запрос: токен/CSRF подставляется автоматически
    const o = await request.send({ to: 'ajax/handler', data: { text: 'hi' } });

    // Выход
    session.logout();

    session.on('authorize', () => console.log('authorized'));
    session.on('logout', () => console.log('logged out'));
</script>
```

Особенности:

- При ответе `401` сессия автоматически сбрасывается (см. перехватчик в конструкторе [`Session`](client/Session.js:60)).
- В cookie-режиме токен недоступен JS — он живёт в httpOnly-cookie; сервер ротирует CSRF-cookie
  только при логине.
- События `authorize` / `logout` позволяют реагировать на смену состояния.

### 3.3 Пример роутинга с авторизацией и шифрованием

Дополнительно к авторизации тело запроса шифруется на клиенте публичным ключом,
выведенным из секрета и токена. Расшифровать его может только сервер, знающий тот же токен.

Схема:

1. При успешном логине сервер отдаёт клиенту публичный ключ `pub`
   ([`crypto::getKeypair()`](server/crypto.php:7) + [`crypto::getPublicKey()`](server/crypto.php:14)).
2. Клиент шифрует `data` через libsodium sealed box и помечает запрос флагом `enc: true`.
3. Сервер при `enc: true` расшифровывает payload тем же токеном и проверяет целостность.

**Серверная часть**

В сессию добавляем выдачу публичного ключа:

```php
<?php
// my-session.php (фрагмент)
use fmihel\router\crypto;

/** данные отправляемые на клиент вместе с токеном */
public static function clientTokenInfo(string $cryptoKey): array
{
    $keypair = crypto::getKeypair($cryptoKey, self::$token);
    $pub     = crypto::getPublicKey($keypair);

    return [
        'token' => self::tokenForClient(),
        'name'  => self::$tokenData['name'],
        'right' => self::data('right'),
        'pub'   => $pub, // публичный ключ для шифрования на клиенте
    ];
}
```

```php
<?php
// config.php (дополнение)
'crypto' => [
    'key' => 'static-secret-for-keypair', // статический секрет
],
```

Точка входа регистрирует распаковщик **до** авторизации: он получает статический ключ
и токен из запроса и расшифровывает `data`, если установлен флаг `enc`.

```php
<?php
// index.php
use fmihel\router\crypto\define;
use fmihel\router\response;
use fmihel\router\router;
use fmihel\router\exceptions\AccessDenied;
use fmihel\router\exceptions\ModuleIsNotDefined;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/my-session.php';

response::$corsOrigins     = config['cors'] ?? '*';
response::$corsCredentials = config['corsCredentials'] ?? false;
response::preflight();

try {
    if (router::enable()) {

        // регистрируем распаковщик шифрованных данных
        define::cryptoUnpacker(function () {
            return [
                config['crypto']['key'],        // статический ключ
                session::tokenFromRequest(),    // токен из запроса
            ];
        });

        session::authorizeFromRequest();

        $module = router::module([__DIR__]);
        if (! file_exists($module)) {
            throw new ModuleIsNotDefined($module);
        }
        require_once $module;
    }
} catch (AccessDenied $e) {
    response::error(response::HTTP_UNAUTHORIZED_401);
} catch (ModuleIsNotDefined $e) {
    response::error(response::HTTP_NOT_FOUND_404);
} catch (\Exception $e) {
    response::error(response::HTTP_INTERNAL_SERVER_ERROR_500);
}
```

После этого `input::data('text')` в модуле вернёт уже расшифрованные данные —
код обработчика не меняется. Подробности распаковки —
в [`server/crypto/define.php`](server/crypto/define.php).

**Клиентская часть**

[`Crypto`](client/Crypto.js) вешается перехватчиком на [`Request`](client/Request.js):
если сессия активна и есть `session.data.pub`, поле `data` шифруется, а в запрос
добавляется `enc: true`.

```html
<script type="importmap">
    {
        "imports": {
            "libsodium": "https://cdn.jsdelivr.net/npm/libsodium@0.8.4/dist/modules-esm/libsodium.mjs",
            "libsodium-wrappers": "https://cdn.jsdelivr.net/npm/libsodium-wrappers@0.8.4/dist/modules-esm/libsodium-wrappers.mjs"
        }
    }
</script>

<script type="module">
    import { Request } from './client/Request.js';
    import { Session } from './client/Session.js';
    import { Crypto } from './client/Crypto.js';

    const request = new Request();
    const session = new Session(request, { mode: 'cookie' });
    const crypto  = new Crypto(request, session); // включает прозрачное шифрование

    // Авторизация выдаёт публичный ключ сервера (session.data.pub)
    await session.authorizeByLogin('1', '1');
    session.authorizeByToken().catch(() => {});

    // Данные шифруются автоматически
    const o = await request.send({ to: 'ajax/handler', data: { text: 'secret' } });

    // Отдельный запрос можно отправить без шифрования
    // await request.send({ to: 'ping', data: {}, skipCrypto: true });
</script>
```

Порядок создания важен: сначала `Request`, затем `Session`, затем `Crypto`
(каждый следующий подписывается на перехватчики предыдущего).

---

## Лицензия

MIT — см. [`LICENSE`](LICENSE).
