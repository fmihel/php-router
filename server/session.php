<?php
namespace fmihel\router;

require_once __DIR__ . '/exceptions/TokenExeption.php';
require_once __DIR__ . '/exceptions/AccessDenied.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use fmihel\router\exceptions\AccessDenied;
use fmihel\router\exceptions\MethodIsNotDefined;

class session
{
    const EXCEPTION       = '-exception-';
    public static $server = 'local';
    public static $key    = 'replace';
    public static $expire = 366;

    public static $token        = false;
    protected static $tokenData = [];

    public static function enable(): bool
    {
        return ! ! self::$token;
    }

    public static function autorizeByLogin(string $login, string $password)
    {
        self::$token     = false;
        self::$tokenData = [];

        try {
            if (empty($tokenData = static::checkLogin($login, $password))) {
                throw new AccessDenied();
            }
            self::$token     = self::createToken($tokenData, self::$key, self::$server, self::$expire);
            self::$tokenData = $tokenData;

        } catch (\Exception $e) {

            self::$token     = false;
            self::$tokenData = [];

            throw $e;
        }
    }

    public static function autorizeByToken(string $token)
    {
        self::$token     = false;
        self::$tokenData = [];

        try {
            $decoded = JWT::decode($token, new Key(self::$key, 'HS256')); // возвращает stdClass
            $unpack  = json_decode(json_encode($decoded), true);          // превращаем в hash

            self::$token     = $token;
            self::$tokenData = $unpack['data'];

        } catch (\Exception $e) {

            self::$token     = false;
            self::$tokenData = [];

            throw new AccessDenied();
        }
    }

    private static function createToken(array $data, string $key, string $server, $expire): string
    {
        $time    = time();
        $payload = [
            'server' => $server,
            'start'  => $time,
            'stop'   => $time + $expire * 86400,
            'data'   => $data,
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');

        return $jwt;
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
