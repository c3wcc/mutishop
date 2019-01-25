<?php

namespace WXAPI\Controller;

use Think\Controller;

class LoginController extends Controller
{
    public function getUserInfo()
    {
        $url = $_GET['url'];
        $url = urldecode($url);
        $result = httpRequest($url, 'GET');

        $arr = json_decode($result, true);

        $userInfo = $this->userInfo($arr['openid'], $arr['unionid']);

        $data = array(
            'open_id' => $arr['openid'],
            'unionid' => $arr['unionid'],
            'userInfo' => $userInfo,
        );

        $this->ajaxReturn($data);
    }

    /**
     * 获取用户信息.
     */
    private function userInfo($open_id, $unionid)
    {
        // $user = M('users')->where(array('unionid' => $unionid))->find();
        // if ($user) {
        //     return $user;
        //     exit;
        // }
        $user = M('users')->where(array('open_id' => $open_id))->find();
        if ($user) {
            return $user;
            exit;
        }
        //最后不存在，注册一个
        $data = array(
            'open_id' => $open_id,
            'unionid' => $unionid,
            'reg_time' => time(),
            'oauth' => 'weixin',
        );

        $user_id = M('users')->data($data)->add();
        $user = M('users')->where(array('user_id' => $user_id))->find();

        if (false == strpos($user['head_pic'], 'http')) {
            $user['head_pic'] = SITE_URL.$user['head_pic'];
        }

        return $user;
    }

    /**
     * 修改用户信息.
     */
    public function editUserInfo()
    {
        $user_id = I('user_id');
        if (!$user_id) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '参数为空'));
            exit;
        }

        $data['country'] = I('country');
        $data['gender'] = I('gender');
        $data['nickname'] = I('nickname');
        $data['nickname'] = I('nickname');
        $data['province'] = I('province');
        $data['city'] = I('city');
        $data['head_pic'] = I('head_pic');

        M('users')->where(array('user_id' => $user_id))->save($data);
    }

    /**
     * 绑定手机号码
     *
     * @param string user_id 用户ID
     * @param string mobile 手机号码
     * @param string code 验证码
     *
     * @return array
     */
    public function bind()
    {
        $user_id = $_GET['user_id'];
        if (!$user_id || null == $user_id || 'undefined' == $user_id) {
            $this->ajaxReturn(array('code' => 400, 'msg' => 'user_id不能为空'));
            exit;
        }
        $code = $_GET['code'];
        if (!$code) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '验证码不能为空'));
            exit;
        }
        $data['mobile'] = $_GET['mobile'];
        if (!$data['mobile']) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '手机号不能为空'));
            exit;
        }
        //验证是否一致
        $sms = M('sms_log')->where(array('mobile' => $data['mobile']))->order('id DESC')->find();
        if ($sms['code'] != $code) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '验证码不匹配'));
            exit;
        }
        $data['mobile_validated'] = 1;
        M('users')->where(array('user_id' => $user_id))->save(array('mobile' => $data['mobile'], 'mobile_validated' => 1));

        M('sms_log')->where(array('mobile' => $data['mobile']))->delete();

        $this->ajaxReturn(array('code' => 200, 'msg' => '绑定成功'));
    }
}
