<?php

namespace WXAPI\Controller;

use Think\Controller;

class SmsController extends Controller
{
    /**
     * 测试.
     */
    public function send()
    {
        $mobile = '13516565558';

        // $templateCode = M('config')->where(array('name'=>'sms_templateCode'))->getField('value');

        // $templateParam = json_encode(array(  // 短信模板中字段的值
        //     "code"=>"12345678",
        //     "product"=>"dsd"
        // ), JSON_UNESCAPED_UNICODE);

        // $SmsLogic = new \Home\Logic\SmsLogic();
        // $res = $SmsLogic->sendSms($mobile,$templateCode,$templateParam);

        // dump($res);

        $code = '445566';
        $res = sendSMS($mobile, $code);
        dump($res);
    }
}
