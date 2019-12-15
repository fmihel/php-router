<?php
namespace fmihel\router\test;

use PHPUnit\Framework\TestCase;
use fmihel\router\lib\ARR;


class ForISAssoc {
    public $a = '';
};


final class ArrTest extends TestCase{

    public function test_is_assoc(){
        $data = ['test'=>10];
        
        self::assertTrue(ARR::is_assoc($data));
        
        $data = ['10',1,2,4,5];
        self::assertFalse(ARR::is_assoc($data));
        
        $data = new ForISAssoc();
        self::assertFalse(ARR::is_assoc($data));
        
    }
}

?>