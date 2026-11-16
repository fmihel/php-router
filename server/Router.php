<?php
namespace fmihel\router;

use fmihel\router\utils\file;

require_once __DIR__ . '/utils/file.php';

class router
{
    public static function enable(): bool
    {
        return input::route();
    }

    public static function module(array $paths)
    {

        $route = file::join(...[ ...$paths, input::route()]);

        if (is_dir($route)) {
            $file = file::join($route, 'index.php');
        } else {
            $file = file::add_ext($route, 'php');
        }

        return $file;
    }
}
