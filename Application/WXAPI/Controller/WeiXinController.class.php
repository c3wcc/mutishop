<?php

namespace WXAPI\Controller;

use Think\Controller;

class WeiXinController extends Controller
{
    public function wxTest()
    {
        echo $_GET['echostr'];
    }

    private function checkSignature()
    {
        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    public function index($object)
    {
        define('TOKEN', 'jkcsoft');
        $this->responseMsg();
    }

    private function transmitText($object, $content)
    {
        $mcount = M('member')->count();
        ++$mcount;
        switch ($object->Event) {
            case 'subscribe':
                $mWeixinTicker = D('weixin_ticker');
                $openid = $object->FromUserName;
                $where = array();
                $where['wxopen_id'] = $openid.'';
                $data = $mWeixinTicker->where($where)->find();
                if (!$data) {
                    $wdata = array();
                    $wdata['wxopen_id'] = $openid.'';
                    $wdata['ticker'] = $object->EventKey.'';
                    $wdata['ticker'] = str_replace('qrscene_', '', $wdata['ticker']);
                    $mWeixinTicker->add($wdata);
                    $member = M('member')->where(array(
                        'uid' => $wdata['ticker'],
                    ))->find();
                } else {
                    $member = M('member')->where(array(
                        'uid' => $data['ticker'],
                    ))->find();
                }
                if ($member) {
                    $content = '您的好友'.$member['nickname'].$member['telphone'].'说了您会来的。
    
1：我们这个平台，主要销售的锁具安防产品，是目前市场上面最好的安防产品。差的产品和仿冒的产品我们一律不做。要做就做好的安防产品。价格统一化，服务现标准化。
    
2：我来给大家介绍一下，什么叫三级推销模式，就是第一个人，推广给第二个人，第三个人只要在商城买任何产品。第一个人就可以获得收益每个产品的收益，比例不同120元，第二人也可以得到收益30元。
    
3：你要是开锁师傅，代理我的锁灵通平台了。我们可以给你认证，认证后，你发展的下线，客户要找开锁、卖锁、换锁全部找到你本人，平台会收去8元钱一单手续费。
    
4：顾客要预约开锁，平台会自动把客户的信息发到你手机上面，有你自己给客户联系。你的代理商城上面，也有顾客留给的要开锁的订单信息。
    
5：要是普通老百姓，做的代理商，发展的开锁业务。有平台接单，接了订单后，顾客区域派单。收取订单的毛利润20%手续费。';

                    $weixin = M('member_weixin')->where(array(
                        'uid' => $member['uid'],
                    ))->find();
                    $APPID = 'wxed2616ed19e63025';
                    $APPSECRET = '109e2bd8b7d7336a197ef689a2ba737e';

                    $TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$APPID.'&secret='.$APPSECRET;

                    $json = file_get_contents($TOKEN_URL);
                    $result = json_decode($json);

                    $ACC_TOKEN = $result->access_token;

                    //$content = $content. $weixin['wxopenid'];
                    $data = '{
        "touser":"'.$weixin['wxopenid'].'",
        "msgtype":"text",
        "text":
        {
             "content":"'.'新的下级'.'"
        }
       }';

                    $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$ACC_TOKEN;

                    $result1 = $this->https_post($url, $data);
                } else {
                    $content = '恭喜您成为“锁灵通大平台”会员，锁灵通会为您全力服务！
    
1：我们这个平台，主要销售的锁具安防产品，是目前市场上面最好的安防产品。差的产品和仿冒的产品我们一律不做。要做就做好的安防产品。价格统一化，服务现标准化。
    
2：我来给大家介绍一下，什么叫三级推销模式，就是第一个人，推广给第二个人，第三个人只要在商城买任何产品。第一个人就可以获得收益每个产品的收益，比例不同120元，第二人也可以得到收益30元。
    
3：你要是开锁师傅，代理我的锁灵通平台了。我们可以给你认证，认证后，你发展的下线，客户要找开锁、卖锁、换锁全部找到你本人，平台会收去8元钱一单手续费。
    
4：顾客要预约开锁，平台会自动把客户的信息发到你手机上面，有你自己给客户联系。你的代理商城上面，也有顾客留给的要开锁的订单信息。
    
5：要是普通老百姓，做的代理商，发展的开锁业务。有平台接单，接了订单后，顾客区域派单。收取订单的毛利润20%手续费。';
                }
                break;
            case 'SCAN':
                $content = '您已经有上级了哦';
                break;
            case 'CLICK':
                $content = '点击获取二维码按钮';
                break;
        }

        $textTpl = '<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>';
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);

        $file = 'log.txt';
        file_put_contents($file, "result:\n", FILE_APPEND);
        $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];

        file_put_contents($file, $result, FILE_APPEND);

        return $result;
    }

    public function https_post($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            return 'Errno'.curl_error($curl);
        }
        curl_close($curl);

        return $result;
    }

    public function responseMsg()
    {
        // get post data, May be due to the different environments
        $file = 'log.txt';
        file_put_contents($file, "read:\n", FILE_APPEND);
        $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];

        file_put_contents($file, $postStr, FILE_APPEND);
        // extract post data
        if (!empty($postStr)) {
            /*
             * libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
             * the best way is to check the validity of xml by yourself
             */
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

            $RX_TYPE = trim($postObj->MsgType);

            switch ($RX_TYPE) {
                case 'event':
                    $resultStr = $this->transmitText($postObj, '');

                    echo $resultStr;

                    return;
                    break;
            }
        } else {
            echo '';
            exit();
        }
    }
}
