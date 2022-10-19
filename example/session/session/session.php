<?php
namespace fmihel;

class session{
    private static $users = [
        ['id'=>'HHSJKDL','pass'=>'1']
    ];
    
    public static $user = [];
    public static $enable = false;

    public static function autorizeByPass($pass){
        foreach(self::$users as $user){
            if($user['pass'] === $pass){
                self::$enable = true;
                self::$user = $user;
                return true;
            }
        };
        return false;
        
    }

    public static function autorizeById($id){
        foreach(self::$users as $user){
            if($user['id'] === $id){
                self::$enable = true;
                self::$user = $user;
                return true;
            }
        };
        return false;
        
    }

    public static function logout(){
        self::$enable = false;
        self::$user = [];
    }

}


router::on('before',function($pack){
    if ($pack['to'] !=='session/autorize' && $pack['to'] !=='session/logout'){

        if (!isset($pack['session']['id']) || !session::autorizeById($pack['session']['id']) ){
            throw new \Exception('need autorize');
        };

    }

    return $pack;
});

