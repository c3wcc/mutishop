<?php

namespace Api\Controller;

use Think\Controller;

class ShipperController extends Controller
{
    public function getCaptcha()
    {
        $object = file_get_contents('php://input');
        $array = (json_decode($object, true));

        $result = $this->get_url_content('http://localhost:8882/api/captcha/login?phone='.$array['phone'], $array);
        echo $result;
    }

    public function validate()
    {
        $phone = $_GET['phone'];
        $num = $_GET['num'];
        $open_id = $_GET['openid'];

        $res = D('phone_captcha')->where(array('phone' => $phone, 'captcha' => $num))->find();
        if ($res) {
            $res = D('member')->where(array('phone' => $phone))->save(array('wei_xin_id' => $open_id));
            if ($res) {
                echo json_encode(array('code' => '200', 'msg' => '验证成功'));
            } else {
                echo json_encode(array('code' => '400', 'msg' => '手机号码有误'));
            }
        } else {
            echo json_encode(array('code' => '400', 'msg' => '验证码或手机号码有误'));
        }
    }

    public function validateOpenid()
    {
        $open_id = $_GET['openid'];
        $res = D('member')->where(array('wei_xin_id' => $open_id))->find();

        if ($res) {
            $shipper_array = D('shipper')->where(array('user_id' => $res['id']))->find();

            $res['shipperInfo'] = $shipper_array;

            echo json_encode(array('code' => '200', 'msg' => '验证成功', 'data' => $res));
        } else {
            echo json_encode(array('code' => '400', 'msg' => '验证失败'));
        }
    }

    public function get_url_content($url, $data)
    {
        $user_agent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)';
        $data_string = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: '.strlen($data_string),
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function getOrderList()
    {
        $list = D('order')->where(array('shipper_id' => $_GET['id'], 'state' => $_GET['state']))->select();
        if ($list) {
            echo json_encode(array('code' => '200', 'msg' => '成功', 'data' => $list));
        } else {
            echo json_encode(array('code' => '400', 'msg' => '失败'));
        }
    }

    public function getCarrierList()
    {
        $list = D('user')->where(array('parent_id' => 0))->select();
        if ($list) {
            foreach ($list as $key => $value) {
                // echo $value['memberships_id'];
                if (1 == $value['memberships_id']) {
                    $list[$key]['membership_name'] = '零担物流';
                } elseif (2 == $value['memberships_id']) {
                    $list[$key]['membership_name'] = '三方物流';
                } elseif (3 == $value['memberships_id']) {
                    $list[$key]['membership_name'] = '仓储物流';
                } elseif (4 == $value['memberships_id']) {
                    $list[$key]['membership_name'] = '冷链物流';
                } elseif (5 == $value['memberships_id']) {
                    $list[$key]['membership_name'] = '同城配送';
                }
            }
            echo json_encode(array('code' => '200', 'msg' => '成功', 'data' => $list));
        } else {
            echo json_encode(array('code' => '400', 'msg' => '失败'));
        }
    }
}
