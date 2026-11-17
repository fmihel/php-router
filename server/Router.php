<?php
namespace fmihel\router;

use fmihel\router\exceptions\InvalidRoute;
use fmihel\router\utils\file;

require_once __DIR__ . '/utils/file.php';
require_once __DIR__ . '/exceptions/InvalidRoute.php';

class router
{
    public static function enable(): bool
    {
        return input::route();
    }

    /**
     * Формирует путь к файлу-модулю по маршруту из запроса.
     *
     * Маршрут приходит от клиента, поэтому проходит проверку:
     *  - запрет абсолютных путей;
     *  - запрет переходов ".." (path traversal);
     *  - ограничение допустимых символов;
     *  - итоговый путь обязан лежать внутри каталогов $paths.
     *
     * @param string[] $paths базовые каталоги, внутри которых разрешены модули
     * @throws InvalidRoute при некорректном маршруте или выходе за базовый каталог
     */
    public static function module(array $paths)
    {
        $route = input::route();

        self::assertValidRoute($route);

        $base   = file::join(...$paths);
        $target = file::join($base, $route);

        if (is_dir($target)) {
            $file = file::join($target, 'index.php');
        } else {
            $file = file::add_ext($target, 'php');
        }

        self::assertInsideBase($base, $file);

        return $file;
    }

    /** Проверяет маршрут, пришедший от клиента, на безопасность. */
    private static function assertValidRoute(string $route): void
    {
        if (trim($route) === '') {
            throw new InvalidRoute('route is empty');
        }

        // Приводим разделители к единому виду (в т.ч. Windows '\')
        $normalized = str_replace('\\', '/', $route);

        // Абсолютные пути: "/abs", "C:/abs", "C:\abs", "\\server\share", "scheme://host"
        if (strpos($normalized, '/') === 0
            || preg_match('#^[a-z]:#i', $normalized)
            || preg_match('#^[a-z][a-z0-9+.\-]*://#i', $normalized)) {
            throw new InvalidRoute('absolute path is not allowed: ' . $route);
        }

        // Переходы вверх по дереву каталогов
        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '..') {
                throw new InvalidRoute('path traversal is not allowed: ' . $route);
            }
        }

        // Допустимые символы: буквы, цифры, "_", "-", ".", "/"
        if (! preg_match('#^[A-Za-z0-9_./-]+$#', $normalized)) {
            throw new InvalidRoute('route contains illegal characters: ' . $route);
        }
    }

    /**
     * Гарантирует, что итоговый путь лежит внутри базового каталога.
     * Учитывает symlink'и и ещё не существующие файлы/каталоги.
     */
    private static function assertInsideBase(string $base, string $file): void
    {
        $baseReal = realpath($base);
        if ($baseReal === false) {
            throw new InvalidRoute('base directory not found: ' . $base);
        }

        // Файл может ещё не существовать — ищем ближайший существующий каталог
        $dir = dirname($file);
        while (! is_dir($dir)) {
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }

        $dirReal = realpath($dir);
        if ($dirReal === false) {
            throw new InvalidRoute('cannot resolve directory: ' . $dir);
        }

        $baseReal = self::normalizePath($baseReal);
        $dirReal  = self::normalizePath($dirReal);

        if ($dirReal !== $baseReal && strpos($dirReal . '/', $baseReal . '/') !== 0) {
            throw new InvalidRoute('path is outside base directory: ' . $file);
        }
    }

    private static function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
