<?php

use fmihel\router\exceptions\AccessDenied;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/router/session.php';
require_once __DIR__ . '/config.php';

define('USERS', [
    ['id' => 1, 'login' => '1', 'password' => '1', 'name' => "mike", 'right' => 'admin'],
    ['id' => 2, 'login' => '2', 'password' => '2', 'name' => "soma", 'right' => 'user'],
]);

class session extends fmihel\router\session
{

    public static function checkLogin(string $login, string $password): array
    {
        foreach (USERS as $user) {
            if ($user['login'] === $login && $user['password'] === $password) {
                return [
                    'id'   => $user['id'],
                    'name' => $user['name'],
                ];
            }
        }
        throw new AccessDenied();
        // throw new \Exception('access denied');
    }

    protected static function data(string $name, $default = self::EXCEPTION)
    {
        $id   = self::$tokenData['id'];
        $user = false;
        foreach (USERS as $item) {
            if ($item['id'] === $id) {
                $user = $item;
                break;
            }
        }

        if (! $user || ! isset($user[$name])) {
            if ($default === self::EXCEPTION) {
                throw new \Exception('not define ' . $name);
            }

            return $default;
        }

        return $user[$name];

    }

    /** данные отправялемые на клиент вместе с токеном */
    public static function clientTokenInfo(): array
    {
        return [
            'token' => self::$token,
            'name'  => self::$tokenData['name'],
            'right' => self::data('right'),
        ];
    }
}

session::$key = config['jwt']['key'];
