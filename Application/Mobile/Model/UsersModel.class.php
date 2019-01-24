<?php

namespace Mobile\Model;
use Think\Model;


class UsersModel extends Model{
    
    
    public function register($data)
    {
        $user = M('users')->where(["user_id"=>$data['user_id'] ])->find();
        if($user){
            return false;
        }

        $add['user_id'] = $data['user_id'];
        $add['openid'] = $data['openid'];
        $add['head_pic'] = $data['head_pic'];
        M('users')->data($add)->add();

    }
}