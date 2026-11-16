<?php
namespace fmihel\router\exceptions;

class MethodIsNotDefined extends \Exception
{
    public function __construct(string $method)
    {
        parent::__construct("$method is not defined");
    }
}
