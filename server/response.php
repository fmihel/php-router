<?php
namespace fmihel\router;

class response
{
    const EXCEPTION            = '-exception-';
    const HTML_SEND_USE_SCRIPT = 0;
    const HTML_SEND_USE_SERVER = 1;

    const HTTP_OK_200                    = 200; // все ок
    const HTTP_BAD_REQUEST               = 400; // пришли неправильные данные
    const HTTP_UNAUTORIZED_401           = 401; // нужна авторизация
    const HTTP_NOT_FOUND_404             = 404; // не найдено
    const HTTP_INTERBAL_SERVER_ERROR_500 = 500; // ошибка на сервере

    public static function data(array $data, $code = self::HTTP_OK_200)
    {
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Authorization, Content-Type");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

        if ($code != 200) {
            http_response_code($code);
        }

        echo json_encode($data);
        exit();
    }

    public static function error($e, $code = self::HTTP_INTERBAL_SERVER_ERROR_500)
    {
        if (is_a($e, '\Exception')) {
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
