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

        $Model = self;
        $Model->user_id = $data['user_id'];
        $Model->openid = $data['openid'];
        $Model->head_pic = $data['head_pic'];
        $Model->save();

    }
}