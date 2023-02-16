<?php

namespace fmihel;

interface iRouterPlugin{
   
    public function setRouter($router);
    // должен возвращат $pack
    public function before($pack);
    // должен возвращат $pack
    public function after($pack);
    
};