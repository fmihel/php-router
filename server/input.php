<?php
namespace fmihel\router;

class input
{
    const EXCEPTION                    = '-exception-';
    private static $_data              = null;
    private static $_data_is_ready     = false;
    private static $_route             = null;
    private static $_route_is_ready    = false;
    private static $_envelope          = null;
    private static $_envelope_is_ready = false;

    /** @var callable распаковщик, регистрируются снаружи */
    private static $_unpacker = null;

    public static function data($name = '', $default = self::EXCEPTION)
    {
        try {

            $data = self::getEnv('data');

            if (empty($name)) {
                return $data;
            }

            if (! isset($data[$name])) {

                throw new \Exception("$name is not defined");
            }

            return $data[$name];

        } catch (\Exception $e) {

            if ($default === self::EXCEPTION) {
                throw $e;
            } else {
                return $default;
            }
        }
    }

    public static function route(): string
    {
        return self::getEnv('route');
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

    private static function _load_env_route()
    {
        if (! self::$_route_is_ready) {
            self::$_route_is_ready = true;
            if (isset($_GET['to'])) {
                self::$_route = $_GET['to'];
            } else {
                self::$_route = self::$_envelope['to'] ?? '';
            }
        }
    }

    private static function _load_env_data()
    {
        if (! self::$_data_is_ready) {
            self::$_data_is_ready = true;
            $raw                  = self::$_envelope['data'] ?? [];
            $GET                  = $_GET ?? [];
            unset($GET['to']);
            self::$_data = array_merge(
                $GET,
                self::unpack($raw, self::$_envelope)
            );
        }
    }

    private static function getEnv(string $name, $default = self::EXCEPTION)
    {
        if (! self::$_envelope_is_ready) {
            self::$_envelope_is_ready = true;
            $decoded                  = json_decode(file_get_contents('php://input'), true);
            self::$_envelope          = is_array($decoded) ? $decoded : [];

        }

        if ($name === 'route') {
            self::_load_env_route();
            return self::$_route;
        }

        if ($name === 'data') {
            self::_load_env_data();
            return self::$_data;
        }

        if ($default === self::EXCEPTION) {
            throw new \Exception("$name is not defined");
        }

        return $default;
    }

    /** Уастанвливает распаковщик. Контракт: (mixed $raw, array $envelope) => mixed|null */
    public static function setUnpacker(callable $fn): void
    {
        self::$_unpacker = $fn;
    }

    private static function unpack($raw, array $envelope)
    {
        if (self::$_unpacker === null) {
            return $raw;
        }
        $res = (self::$_unpacker)($raw, $envelope);
        return $res === null ? $raw : $res;
    }
}
