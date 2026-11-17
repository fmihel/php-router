<?php
namespace fmihel\router\exceptions;

class InvalidRoute extends \Exception
{
    public function __construct(string $msg = 'Invalid route')
    {
        parent::__construct($msg);
    }
}
