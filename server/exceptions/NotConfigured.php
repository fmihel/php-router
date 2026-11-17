<?php
namespace fmihel\router\exceptions;

/** Бросается, когда библиотека не сконфигурирована (например, не задан секретный ключ JWT). */
class NotConfigured extends \Exception
{
    public function __construct(string $msg = 'Router is not configured')
    {
        parent::__construct($msg);
    }
}
