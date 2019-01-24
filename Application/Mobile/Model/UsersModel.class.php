<?php

namespace Mobile\Model;
use Think\Model;


class UsersModel extends Model{
    
    /**
     * 注册
     * 更新头像
     */
    public function register($data)
    {
        if(!$data['user_id']){
            $this->error('注册出错');
        }

        $user = M('users')->where(["user_id"=>$data['user_id'] ])->find();
        if($user){
            //更新信息
            if($user['nickname'] != $data['nickname']){
                M('users')->where(["user_id"=>$data['user_id']])->update(['nickname' => $data['nickname']]);
            }

            if($user['head_pic'] != $data['head_pic']){
                M('users')->where(["user_id"=>$data['user_id']])->update(['head_pic' => $data['head_pic']]);
            }

            return false;
        }

        $add['user_id'] = $data['user_id'];
        $add['openid'] = $data['openid'];
        $add['nickname'] = $data['nickname'];
        $add['head_pic'] = $data['head_pic'];
        M('users')->data($add)->add();

    }
}