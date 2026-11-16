<?php
namespace fmihel\router\utils;

class file
{
    /** конкатенация имен маршрутов */
    public static function join(string ...$paths): string
    {
        if (count($paths) === 0) {
            return '';
        }

        foreach ($paths as $key => $path) {
            $paths[$key] = str_replace('/', '\\', $path);
        }

        $root    = ':\\';
        $rootKey = '<%ROOT%>';

        $out = str_replace($root, $rootKey, join('/', $paths));
        $out = explode('/', $out);
        $out = array_filter($out, function ($value) {return ! is_null($value) && $value !== '';});

        $other = [];
        foreach ($out as $a) {
            $a     = explode('\\', $a);
            $other = array_merge($other, array_filter($a, function ($value) {return ! is_null($value) && $value !== '';}));
        }
        $out = $other;

        $out = join('/', $out);
        $out = str_replace($rootKey, $root, $out);

        if (strpos($out, $root) === false) {
            $out = '/' . $out;
        }
        return $out;
    }

    /** добавляет расширение к файлу если нет расширения */
    public static function add_ext(string $filename, string $defaultExt): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if ($ext === '') {
            // Убираем лишнюю точку, если её передали в $defaultExt (например, '.txt' -> 'txt')
            $defaultExt = ltrim($defaultExt, '.');
            // Убираем точку в конце исходного имени, если она там случайно оказалась ('file.' -> 'file')
            return rtrim($filename, '.') . '.' . $defaultExt;
        }

        return $filename;
    }

}
