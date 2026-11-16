<?php
namespace fmihel\router\exceptions;

class ModuleIsNotDefined extends \Exception
{
    public function __construct(string $name)
    {
        parent::__construct("module $name is not defined");
    }
}
