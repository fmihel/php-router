<?php
namespace fmihel\router\exceptions;

class AccessDenied extends \Exception
{
    public function __construct(string $msg = 'Доступ запрещен')
    {
        parent::__construct($msg);
    }
}
