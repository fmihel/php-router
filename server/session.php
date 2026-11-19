<?php
namespace fmihel\router;

require_once __DIR__ . '/exceptions/TokenException.php';
require_once __DIR__ . '/exceptions/AccessDenied.php';
require_once __DIR__ . '/exceptions/NotConfigured.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use fmihel\router\exceptions\AccessDenied;
use fmihel\router\exceptions\MethodIsNotDefined;
use fmihel\router\exceptions\NotConfigured;

class session
{
    const EXCEPTION   = '-exception-';
    const DEFAULT_KEY = 'replace';

    /** issuer: токен действителен только для этого сервера/приложения */
    public static $server = 'local';

    /** секретный ключ; значение по умолчанию недопустимо (см. assertConfigured) */
    public static $key = self::DEFAULT_KEY;

    /** время жизни токена в ДНЯХ (переводится в стандартный claim exp) */
    public static $expire = 366;

    /**
     * Способ хранения токена на клиенте:
     *  - 'header' — Bearer-токен, клиент хранит его сам (localStorage/sessionStorage);
     *  - 'cookie' — httpOnly-cookie, клиент токен не видит, защита от XSS;
     *              для изменяющих методов включается CSRF double-submit.
     */
    public static $tokenMode = 'header';

    /** имя httpOnly-cookie с токеном */
    public static $cookieName = 'jwt';
    /** имя cookie с CSRF-токеном (НЕ httpOnly, чтобы JS мог его прочитать) */
    public static $csrfCookie = 'csrf_token';
    /** заголовок, в котором клиент присылает CSRF-токен */
    public static $csrfHeader = 'X-CSRF-Token';
    /** методы, для которых требуется CSRF-проверка (кроме безопасных GET/HEAD/OPTIONS) */
    public static $csrfMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public static $cookiePath     = '/';
    public static $cookieDomain   = '';
    public static $cookieSameSite = 'Lax';
    /** null — определять автоматически по HTTPS */
    public static $cookieSecure = null;

    protected static $token        = false;
    protected static $tokenData = [];

    public static function enable(): bool
    {
        return ! ! self::$token;
    }

    public static function isCookieMode(): bool
    {
        return self::$tokenMode === 'cookie';
    }

    public static function authorizeByLogin(string $login, string $password)
    {
        self::reset();

        try {
            if (empty($tokenData = static::checkLogin($login, $password))) {
                throw new AccessDenied();
            }

            self::$token     = self::createToken($tokenData);
            self::$tokenData = $tokenData;

            self::issue(self::$token);

        } catch (\Exception $e) {
            self::reset();
            throw $e;
        }
    }

    public static function authorizeByToken(string $token)
    {
        self::assertConfigured();
        self::reset();

        try {
            $decoded = JWT::decode($token, new Key(self::$key, 'HS256')); // проверяет exp/iat/nbf
            $unpack  = json_decode(json_encode($decoded), true);          // превращаем в hash

            if (! is_array($unpack)) {
                throw new \UnexpectedValueException('bad token payload');
            }

            // Токен должен быть выпущен этим сервером
            if (($unpack['iss'] ?? null) !== self::$server) {
                throw new \UnexpectedValueException('issuer mismatch');
            }

            // Наличие exp обязательно (по нему firebase/jwt проверяет срок жизни)
            if (! isset($unpack['exp'])) {
                throw new \UnexpectedValueException('exp is missing');
            }

            if (! isset($unpack['data']) || ! is_array($unpack['data'])) {
                throw new \UnexpectedValueException('data is missing');
            }

            self::$token     = $token;
            self::$tokenData = $unpack['data'];

            self::issue($token);

        } catch (\Exception $e) {
            self::reset();
            throw new AccessDenied();
        }
    }

    /**
     * Авторизация по токену из текущего запроса: ищет Bearer-заголовок,
     * а в cookie-режиме — httpOnly-cookie. Для изменяющих методов проверяет CSRF.
     */
    public static function authorizeFromRequest(): void
    {
        self::assertCsrf();

        $token = self::tokenFromRequest();
        if (! $token) {
            throw new AccessDenied();
        }

        self::authorizeByToken($token);
    }

    /** Сброс авторизации + удаление cookie (для серверного logout). */
    public static function logout(): void
    {
        self::reset();
        if (self::isCookieMode()) {
            self::clearCookies();
        }
    }

    /** Токен, который нужно вернуть клиенту (в cookie-режиме не отдаётся). */
    protected static function tokenForClient(): ?string
    {
        return self::isCookieMode() ? null : (self::$token ?: null);
    }

    /** Проверка CSRF (double-submit cookie) для cookie-режима. */
    private static function assertCsrf(): void
    {
        if (! self::isCookieMode()) {
            return;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (! in_array($method, self::$csrfMethods, true)) {
            return;
        }

        $cookie = $_COOKIE[self::$csrfCookie] ?? '';
        $header = self::readHeader(self::$csrfHeader);

        if ($cookie === '' || $header === '' || ! hash_equals($cookie, $header)) {
            throw new AccessDenied('Invalid CSRF token');
        }
    }

    private static function createToken(array $data): string
    {
        self::assertConfigured();

        $now     = time();
        $payload = [
            'iss'  => self::$server,                      // issuer
            'iat'  => $now,                               // issued at
            'exp'  => $now + (int) self::$expire * 86400, // expiration
            'data' => $data,
        ];

        return JWT::encode($payload, self::$key, 'HS256');
    }

    /** Сохраняет выпущенный/принятый токен согласно выбранному режиму. */
    private static function issue(string $token): void
    {
        if (self::isCookieMode()) {
            self::setTokenCookie($token);
            self::rotateCsrfCookie();
        }
    }

    private static function reset(): void
    {
        self::$token     = false;
        self::$tokenData = [];
    }

    private static function assertConfigured(): void
    {
        if (self::$key === '' || self::$key === null || self::$key === self::DEFAULT_KEY) {
            throw new NotConfigured('session::$key is not configured');
        }
    }

    public static function tokenFromRequest(): ?string
    {
        // 1. Bearer-заголовок
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($auth === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if ($auth !== '' && preg_match('/Bearer\s+(\S+)/', $auth, $m)) {
            return $m[1];
        }

        // 2. httpOnly-cookie (только в cookie-режиме)
        if (self::isCookieMode() && ! empty($_COOKIE[self::$cookieName])) {
            return $_COOKIE[self::$cookieName];
        }

        return null;
    }

    private static function readHeader(string $name): string
    {
        $normalized = str_replace('-', '_', strtoupper($name));
        $serverKey  = 'HTTP_' . $normalized;
        if (! empty($_SERVER[$serverKey])) {
            return $_SERVER[$serverKey];
        }

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $key => $value) {
                if (str_replace('-', '_', strtoupper($key)) === $normalized) {
                    return $value;
                }
            }
        }

        return '';
    }

    private static function setTokenCookie(string $token): void
    {
        self::setCookie(self::$cookieName, $token, time() + (int) self::$expire * 86400, true);
    }

    private static function rotateCsrfCookie(): void
    {
        self::setCookie(self::$csrfCookie, bin2hex(random_bytes(32)), 0, false);
    }

    private static function clearCookies(): void
    {
        self::setCookie(self::$cookieName, '', time() - 3600, true);
        self::setCookie(self::$csrfCookie, '', time() - 3600, false);
    }

    private static function setCookie(string $name, string $value, int $expires, bool $httpOnly): void
    {
        if (headers_sent()) {
            return;
        }

        $secure = self::$cookieSecure;
        if ($secure === null) {
            $secure = ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        }

        $options = [
            'expires'  => $expires,
            'path'     => self::$cookiePath,
            'httponly' => $httpOnly,
            'samesite' => self::$cookieSameSite,
            'secure'   => $secure,
        ];

        if (self::$cookieDomain !== '') {
            $options['domain'] = self::$cookieDomain;
        }

        setcookie($name, $value, $options);
    }

    /** проверка авторизации по логин/паролю, должен вернуть структуру, которая упакуется в token
     * обычно [id,name]
     */
    protected static function checkLogin(string $login, string $password): array
    {
        throw new MethodIsNotDefined(__METHOD__);
    }

    protected static function data(string $name, $default = self::EXCEPTION)
    {
        throw new MethodIsNotDefined(__METHOD__);
    }
}
