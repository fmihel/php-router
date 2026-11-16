<?php
namespace fmihel\router;

class response
{
    const EXCEPTION            = '-exception-';
    const HTML_SEND_USE_SCRIPT = 0;
    const HTML_SEND_USE_SERVER = 1;

    public static function data(array $data, $code = -1)
    {
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Authorization, Content-Type");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

        if ($code > 0) {
            http_response_code($code);
        }
        echo json_encode($data);
        exit();
    }
    public static function error($e, $code = -1)
    {
        $out = [
            'success' => 0,
            'message' => '',
        ];
        if (is_a($e, '\Exception')) {
            $out['message'] = $e->getMessage();
        } else {
            $out['message'] = "$e";
        }
        self::data($out, $code);
    }
    public static function success($payload, $code = -1)
    {
        self::data([
            'success' => 1,
            'payload' => $payload,
        ], $code);
    }
    public static function ok($payload = '', $code = -1)
    {
        self::success($payload, $code);
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
