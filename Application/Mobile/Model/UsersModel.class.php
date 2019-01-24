<?php

namespace Mobile\Model;
use Think\Model;


class UsersModel extends Model{
    
    
    public function register($data)
    {
        $user = self::where(["user_id"=>$data['user_id'] ])->find();
        if($user){
            return false;
        }

        
        $add['user_id'] = $data['user_id'];
        $add['openid'] = $data['openid'];
        $add['head_pic'] = $data['head_pic'];
        self::data($add)->add();

    }
}