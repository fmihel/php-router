<?php
namespace fmihel\router;

class response
{
    const EXCEPTION            = '-exception-';
    const HTML_SEND_USE_SCRIPT = 0;
    const HTML_SEND_USE_SERVER = 1;

    const HTTP_OK_200                    = 200; // все ок
    const HTTP_NO_CONTENT_204            = 204; // запрос успешный, но в теле нет контента
    const HTTP_BAD_REQUEST               = 400; // пришли неправильные данные
    const HTTP_UNAUTHORIZED_401          = 401; // нужна авторизация
    const HTTP_NOT_FOUND_404             = 404; // не найдено
    const HTTP_INTERNAL_SERVER_ERROR_500 = 500; // ошибка на сервере

    /**
     * Настройка CORS.
     *  - $corsOrigins === '*'                    — разрешить любой origin;
     *  - $corsOrigins === ['https://app.local']  — белый список origin.
     * При белом списке запрошенный Origin возвращается, только если он разрешён,
     * и добавляется заголовок Vary: Origin.
     *
     * @var string|string[]
     */
    public static $corsOrigins = '*';

    /** Разрешать cookie/credentials в кросс-доменных запросах (нельзя вместе с '*'). */
    public static $corsCredentials = false;

    public static $corsHeaders = 'Authorization, Content-Type, X-CSRF-Token';
    public static $corsMethods = 'GET, POST, OPTIONS';

    /** Применить CORS-заголовки к текущему ответу. */
    private static function cors(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (self::$corsOrigins === '*') {
            // С credentials браузер запрещает '*' — отражаем конкретный origin
            if (self::$corsCredentials && $origin !== '') {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Vary: Origin');
            } else {
                header('Access-Control-Allow-Origin: *');
            }
        } else {
            $allowed = is_array(self::$corsOrigins) ? self::$corsOrigins : [self::$corsOrigins];
            header('Vary: Origin');
            if ($origin !== '' && in_array($origin, $allowed, true)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                if (self::$corsCredentials) {
                    header('Access-Control-Allow-Credentials: true');
                }
            }
        }

        header('Access-Control-Allow-Headers: ' . self::$corsHeaders);
        header('Access-Control-Allow-Methods: ' . self::$corsMethods);
    }

    /** Завершает обработку preflight-запроса (OPTIONS). */
    public static function preflight(): void
    {
        self::cors();
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(self::HTTP_NO_CONTENT_204);
            exit();
        }
    }

    public static function data(array $data, $code = self::HTTP_OK_200)
    {
        header("Content-Type: application/json; charset=UTF-8");
        self::cors();

        if ($code != 200) {
            http_response_code($code);
        }

        echo json_encode($data);
        exit();
    }

    public static function error($e, $code = self::HTTP_INTERNAL_SERVER_ERROR_500)
    {
        if ($e instanceof \Exception) {
            $message = $e->getMessage();
        } else {
            $message = "$e";
        }
        self::data([
            'success' => false,
            'message' => $message,
        ], $code);
    }

    public static function success($payload = [])
    {
        self::data([
            'success' => true,
            'payload' => $payload,
        ], 200);
    }

    public static function ok($payload = '')
    {
        self::success($payload);
    }

    public static function html(string $file, int $sendType = self::HTML_SEND_USE_SCRIPT)
    {
        if (! file_exists($file)) {
            throw new \Exception("not exist $file");
        }

        header('Content-Type: text/html; charset=utf-8');
        if ($sendType === self::HTML_SEND_USE_SCRIPT) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Length: ' . filesize($file));
            readfile($file);
        } else {
            header('X-Sendfile: ' . $file);
        }
        exit;
    }
}
