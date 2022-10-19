<?php
namespace fmihel;

interface iRouterRule{
    public function adapt($root,$path); 
};