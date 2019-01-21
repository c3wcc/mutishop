<?php

namespace WXAPI\Controller;

class DistributController extends BaseController
{
    /**
     * 析构流函数.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取分销条件.
     */
    public function condition()
    {
        $condition = M('config')->where(array('name' => 'condition'))->getField('value');
        $user_id = I('user_id');
        if ($user_id) {
            $is_distribut = M('users')->where(array('user_id' => $user_id))->getField('is_distribut');
        }

        $data = array(
            'condition' => $condition,
            'is_distribut' => $is_distribut,
        );
        $json_arr = array('status' => 1, 'msg' => '成功', 'result' => $data);
        $json_str = json_encode($json_arr);
        exit($json_str);
    }


     /**
     * 添加分销
     */
    public function add()
    {
        $user_id = $_GET['user_id'];

        if (!$user_id) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '用户id不能为空'));
            exit;
        }

        //下级
        //首先确保这个用户，的first_leader不能为空为0
        $first = M('users')->where(array('user_id' => $user_id))->getField('first_leader');
        if ($first > 0) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '已经存在上级'));
            exit;
        }

        $first_leader = (int) $_GET['first_leader'];
        if (0 == $first_leader) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '上级不能为空'));
            exit;
        }

        $data['first_leader'] = $first_leader;
        //上级
        if ($data['first_leader'] > 0) {
            $res = M('users')->where(array('user_id' => $data['first_leader']))->find();
            $data['second_leader'] = $res['first_leader'];
            $data['third_leader'] = $res['second_leader'];
        }
        M('users')->where(array('user_id' => $user_id))->data($data)->save();
        $this->ajaxReturn(array('code' => 200, 'msg' => '添加成功'));
    }
}
