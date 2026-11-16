<?php
namespace fmihel\router;

use fmihel\router\exceptions\TokenException;

class input
{
    const EXCEPTION       = '-exception-';
    private static $data  = null;
    private static $route = '';

    public static function data($name = '', $default = self::EXCEPTION)
    {
        try {

            self::loadData();

            if (empty($name)) {
                return self::$data;
            }

            if (! isset(self::$data[$name])) {

                throw new \Exception("$name is not defined");
            }

            return self::$data[$name];

        } catch (\Exception $e) {

            if ($default === self::EXCEPTION) {
                throw $e;
            } else {
                return $default;
            }
        }
    }

    public static function token(): string
    {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? getallheaders()['Authorization'] ?? getallheaders()['authorization'] ?? null;
        if (! $auth_header || ! preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            throw new TokenException('no token in header');

            // http_response_code(401);
            // echo json_encode(["success" => false, "message" => "Доступ запрещен. Токен отсутствует или некорректен."]);
            // exit();
        }
        return $matches[1];
    }

    public static function route(): string
    {
        self::loadData();
        return self::$route;
    }

    public static function getHeaderVar(string $name, $default = self::EXCEPTION): string
    {
        $name = strtoupper(trim($name));
        if (strpos($name, 'HTTP_') !== 0) {
            $name = 'HTTP_' . $name;
        }
        $name = str_replace('-', '_', $name);

        if (isset($_SERVER[$name])) {
            // Получаем значение и декодируем обратно из URL-формата (если кодировали в JS)
            $rawHeader = $_SERVER[$name];
            $myData    = urldecode($rawHeader);

            return htmlspecialchars($myData);
        }
        if ($default === self::EXCEPTION) {
            throw new \Exception("not exists $name");
        }
        return $default;
    }
    private static function loadData()
    {
        if (self::$data === null) {
            $data        = json_decode(file_get_contents("php://input"), true);
            self::$data  = $data['data'] ?? [];
            self::$route = $data['to'] ?? false;
        }

    }

}
