<?php

namespace WXAPI\Controller;

use WXAPI\Logic\CartLogic;

class CartController extends BaseController
{
    public $cartLogic; // 购物车逻辑操作类

    /**
     * 析构流函数.
     */
    public function __construct()
    {
        parent::__construct();
        $this->cartLogic = new CartLogic();

        $unique_id = I('unique_id'); // 唯一id  类似于 pc 端的session id
        //$user_id = I("user_id",0); // 用户id
        // 给用户计算会员价 登录前后不一样
        if ($this->user_id) {
            $user = M('users')->where("user_id = {$this->user_id}")->find();
            M('Cart')->execute("update `__PREFIX__cart` set member_goods_price = goods_price * {$user[discount]} where (user_id ={$user[user_id]} or session_id = '{$unique_id}') and prom_type = 0");
        }
    }

    /**
     * 将商品加入购物车.
     */
    public function addCart()
    {
        $goods_id = I('goods_id'); // 商品id
        $goods_num = I('goods_num'); // 商品数量
        $goods_spec = I('goods_spec'); // 商品规格
        $this->user_id = I('user_id');

        if ('' != $goods_spec) {
            $goods_spec = explode('_', $goods_spec);
        } else {
            $goods_spec = null;
        }
        //$goods_spec = json_decode($goods_spec, true); //app 端 json 形式传输过来
        $unique_id = I('session_id'); // 唯一id  类似于 pc 端的session id
        //$user_id = I("user_id",0); // 用户id
        $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec, $unique_id, $this->user_id); // 将商品加入购物车

        exit(json_encode($result));
    }

    public function updateNum()
    {
        $id = I('id');
        $num = I('num');
        $res = M('Cart')->where(array('id' => $id))->save(array('goods_num' => $num));
        $return_arr = array('status' => 1, 'msg' => '成功', 'result' => 0); // 返回结果状态
        exit(json_encode($return_arr));
    }

    public function updateSelect()
    {
        $id = I('id');
        $selected = I('selected');
        $res = M('Cart')->where(array('id' => $id))->save(array('selected' => $selected));
        $return_arr = array('status' => 1, 'msg' => '成功', 'result' => 0); // 返回结果状态
        exit(json_encode($return_arr));
    }

    public function updateAllSelect()
    {
        $session_id = I('open_id');
        $selected = I('selected');
        $res = M('Cart')->where(array('session_id' => $session_id))->select();
        foreach ($res as $value) {
            $res = M('Cart')->where(array('id' => $value['id']))->save(array('selected' => $selected));
        }

        $return_arr = array('status' => 1, 'msg' => '成功', 'result' => 0); // 返回结果状态
        exit(json_encode($return_arr));
    }

    /**
     * 删除购物车的商品
     */
    public function delCart()
    {
        $id = I('id'); // 商品 ids
        $result = M('Cart')->where(array('id' => $id))->delete(); // 删除id为5的用户数据

        // 查找购物车数量
        //$unique_id = I("uid"); // 唯一id  类似于 pc 端的session id
        //$cart_count = cart_goods_num($unique_id);
        $return_arr = array('status' => 1, 'msg' => '删除成功', 'result' => 0); // 返回结果状态
        exit(json_encode($return_arr));
    }

    /*
     * 请求获取购物车列表
     */
    public function cartList()
    {
        //$cart_form_data = $_POST["cart_form_data"]; // goods_num 购物车商品数量
        //$cart_form_data = json_decode($cart_form_data, true); //app 端 json 形式传输过来

        $unique_id = I('session_id'); // 唯一id  类似于 pc 端的session id
        $this->user_id = I('user_id', 0); // 用户id
        $where = " session_id = '$unique_id' "; // 默认按照 $unique_id 查询
        $this->user_id && $where = $where.'or user_id = '.$this->user_id; // 如果这个用户已经等了则按照用户id查询

        $cartList = M('Cart')->where($where)->select();

        foreach ($cartList as $key => $value) {
            $cartList[$key] = array_merge(M('goods')->where(array('goods_id' => $value['goods_id']))->find(), $value);
            $cartList[$key]['image'] = SITE_URL.$cartList[$key]['original_img'];

            if ($this->user_id) {
                $res = M('cart')->where(array('id' => $value['id']))->save(array('user_id' => $this->user_id));
            }
            $cartList[$key]['store_name'] = M('store')->where(array('store_id' => $value['store_id']))->getField('store_name');
        }

        /*if ($cart_form_data) {
            // 修改购物车数量 和勾选状态
            foreach ($cart_form_data as $key => $val) {
                $data['goods_num'] = $val['goodsNum'];
                $data['selected'] = $val['selected'];
                $cartID = $val['cartID'];
                if (($cartList[$cartID]['goods_num'] != $data['goods_num']) || ($cartList[$cartID]['selected'] != $data['selected']))
                    M('Cart')->where("id = $cartID")->save($data);
            }
            //$this->assign('select_all', $_POST['select_all']); // 全选框
        }
*/
        $result = $cartList;
        // if(empty($result['total_price']))
        //     $result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0, 'atotal_fee' =>0, 'acut_fee' =>0, 'anum' => 0);
        //  $result['result']['total_price'] = $result['total_price'];

        exit(json_encode($result));
    }

    /**
     * 购物车第二步确定页面.
     */
    public function cart2()
    {
        $this->user_id = I('user_id');
        $unique_id = I('unique_id'); // 唯一id  类似于 pc 端的session id
        //$user_id = I("user_id"); // 用户id
        $usersInfo = get_user_info($this->user_id);  // 用户

        if (0 == $this->user_id) {
            exit(json_encode(array('status' => -1, 'msg' => '用户id不能为空', 'result' => '')));
        }
        if (0 == $this->cartLogic->cart_count($this->user_id, 1)) {
            exit(json_encode(array('status' => -2, 'msg' => '购物车没有商品', 'result' => '')));
        }

        // 购物车商品
        $cart_result = $this->cartLogic->cartList($usersInfo, $unique_id, 1, 1); // 获取购物车商品
        // 没有选中的不传递过去

        $cartList = array();
        $store_id = 0;
        foreach ($cart_result['cartList'] as $key => $val) {
            if (1 == $val['selected']) {
                if ($val['store_id'] != $store_id) {
                    $val['show'] = 1;
                    $store_id = $val['store_id'];
                    $store_id_arr[] = $store_id;
                }
                $cartList[] = $val;
            }
        }

        foreach ($cartList as $key => $value) {
            $cartList[$key] = array_merge(M('goods')->where(array('goods_id' => $value['goods_id']))->find(), $value);
            $cartList[$key]['image'] = SITE_URL.$cartList[$key]['original_img'];
            $cartList[$key]['store_name'] = M('store')->where(array('store_id' => $value['store_id']))->getField('store_name');
        }

        $cart_result['cartList'] = $cartList;
        // 物流公司
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->select(); // 物流公司
        // 优惠券
        $Model = new \Think\Model(); // 找出这个用户的优惠券 没过期的  并且 订单金额达到 condition 优惠券指定标准的

        $sql = "select c1.name,c1.money,c1.condition, c2.* from __PREFIX__coupon as c1 inner join __PREFIX__coupon_list as c2  on c2.cid = c1.id and c1.type in(0,1,2,3) and order_id = 0
        where c2.uid = {$this->user_id}  and ".time()." < c1.use_end_time and c1.condition <= {$cart_result['total_price']['total_fee']} ";
        if (!empty($store_id_arr)) {
            $sql .= 'and c2.store_id in ('.implode(',', $store_id_arr).')';
        }

        $couponList = $Model->query($sql);
        //print_r($couponList);
        // 收货地址

        $addresslist = M('UserAddress')->where("user_id = {$this->user_id}")->select();

        $c = M('UserAddress')->where("user_id = {$this->user_id} and is_default = 1")->count(); // 看看有没默认收货地址

        if ((count($addresslist) > 0) && (0 == $c)) { // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
            $addresslist[0]['is_default'] = 1;
        } elseif (count($addresslist) > 0) {
            $addresslist[0] = M('UserAddress')->where("user_id = {$this->user_id} and is_default = 1")->find();
        }
        $points = M('config')->where(array('name' => 'point_rate'))->getField('value');
        $json_arr = array(
            'status' => 1,
            'msg' => '获取成功',
            'result' => array(
                'addressList' => $addresslist[0], // 收货地址
                //'shippingList' => $shippingList, //物流列表
                'cartList' => $cart_result['cartList'], // 购物车列表
                'totalPrice' => $cart_result['total_price'], // 总计
                'couponList' => $couponList, //优惠券列表
                'userInfo' => $usersInfo, // 用户详情
                'points' => $points,
            ), );
        exit(json_encode($json_arr));
    }

    /**
     * 获取订单商品价格 或者提交 订单.
     */
    public function cart3()
    {
        //$unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        $this->user_id = I('user_id'); // 用户id
        $usersInfo = get_user_info($this->user_id);  // 用户
        $address_id = I('address_id'); //  收货地址id
        $shipping_code = array('tiantian'); //  物流编号
        $invoice_title = I('invoice_title', '苏州某公司'); // 发票
        $couponTypeSelect = array(1); //  优惠券类型  1 下拉框选择优惠券 2 输入框输入优惠券代码
        $coupon_id = I('coupon_id', 0); //  优惠券id
        $couponCode = array(); //  优惠券代码
        $pay_points = I('pay_points', 0); //  使用积分
        $user_money = I('user_money', 0); //  使用余额
        $user_money = $user_money ? $user_money : 0;
        $user_note = array(); // $user_note = I('user_note'); // 给卖家留言      数组形式

        $cv = $coupon_id;
        $s_stored_id = M('coupon_list')->where(array('id' => $cv))->getField('store_id');
        if ($s_stored_id) {
            $coupon_array[$s_stored_id] = $cv;
        } else {
            $coupon_array = array();
        }
        $coupon_id = $coupon_array;
        //print_r($coupon_id);
        //return ;
        if (0 == $this->cartLogic->cart_count($this->user_id, 1)) {
            exit(json_encode(array('status' => -1, 'msg' => '购物车为空', 'result' => null)));
        } // 返回结果状态
        if (!$address_id) {
            exit(json_encode(array('status' => -1, 'msg' => '请完善收货信息', 'result' => null)));
        } // 返回结果状态
        // if (!$shipping_code) {
        //     exit(json_encode(array('status' => -1, 'msg' => '请选择物流信息', 'result' => null)));
        // } // 返回结果状态

        $address = M('UserAddress')->where("address_id = $address_id")->find();
        $order_goods = M('cart')->where("user_id = {$this->user_id} and selected = 1")->select();
        $result = calculate_price($this->user_id, $order_goods, $shipping_code, 0, $address[province], $address[city], $address[district], $pay_points, $user_money, $coupon_id, $couponCode);
        //print_r($result);
        //return ;
        if ($result['status'] < 0) {
            exit(json_encode($result));
        }

        $car_price = array(
                    'postFee' => $result['result']['shipping_price'], // 物流费
                    'couponFee' => $result['result']['coupon_price'], // 优惠券
                    'balance' => $result['result']['user_money'], // 使用用户余额
                    'pointsFee' => $result['result']['integral_money'], // 积分支付
                    'payables' => array_sum($result['result']['store_order_amount']), // 订单总额 减去 积分 减去余额
                    'goodsFee' => $result['result']['goods_price'], // 总商品价格
                    'order_prom_amount' => array_sum($result['result']['store_order_prom_amount']), // 总订单优惠活动优惠了多少钱

                    'store_order_prom_id' => $result['result']['store_order_prom_id'], // 每个商家订单优惠活动的id号
                    'store_order_prom_amount' => $result['result']['store_order_prom_amount'], // 每个商家订单活动优惠了多少钱
                    'store_order_amount' => $result['result']['store_order_amount'], // 每个商家订单优惠后多少钱, -- 应付金额
                    'store_shipping_price' => $result['result']['store_shipping_price'],  //每个商家的物流费
                    'store_coupon_price' => $result['result']['store_coupon_price'],  //每个商家的优惠券抵消金额
                    'store_point_count' => $result['result']['store_point_count'], // 每个商家平摊使用了多少积分
                    'store_balance' => $result['result']['store_balance'], // 每个商家平摊用了多少余额
                    'store_goods_price' => $result['result']['store_goods_price'], // 每个商家的商品总价
            );

        // 提交订单
        if ('submit_order' == $_REQUEST['act']) {
            $result = $this->cartLogic->addOrder($this->user_id, $address_id, $shipping_code, $invoice_title, $coupon_id, $car_price, $user_note); // 添加订单
            $order = M('order')->where(array('master_order_sn' => $result['result']))->select();
            $result['data'] = $this->getWXPayInfo($result['result']);
            $result['order'] = $order;
            exit(json_encode($result));
        }
        $return_arr = array('status' => 1, 'msg' => '计算成功', 'result' => $car_price); // 返回结果状态
        exit(json_encode($return_arr));
    }

    /**
     * 检查是否支付成功
     */
    public function checkWXPayData()
    {
        $order_id = $_GET['order_id'];
        //检查是否支付

        //响应接口
        $out_trade_no = $_GET['out_trade_no'];
        if ($out_trade_no) {
            $order_id = M('order')->where(array('master_order_sn' => $out_trade_no))->getField('order_id');
        }

        $order = M('order')->where(array('order_id' => $order_id))->field('order_id,pay_status,master_order_sn')->find();
        if (1 == $order['pay_status']) {
            echo 'SUCCESS';
            exit;
        }

        $master_order_sn = $order['master_order_sn'];
        $pay = M('pay')->where(array('out_trade_no' => $master_order_sn))->find();

        if ('SUCCESS' == $pay['result_code'] || 'SUCCESS' == $pay['return_code']) {
            //更改支付状态
            M('order')->where(array('order_id' => $order_id))->save(array('pay_status' => 1));
            M('pay')->where(array('out_trade_no' => $master_order_sn))->save(array('order_id' => $order_id));
            echo 'SUCCESS';
            exit;
        }
    }

    /**
     * 获得微新支付数据.
     */
    public function getWXPayData()
    {
        $order_id = $_GET['order_id'];

        $order = M('order')->where(array('master_order_sn' => $order_id))->find();

        if (0 == (float) $order['order_amount']) {
            update_pay_status($order['order_sn'], 1);
            $total_fee = $order['order_amount'];
            $res['pay_money'] = $total_fee * 100;
            $return_arr = array('status' => 1, 'msg' => '支付成功', 'result' => $res ); // 返回结果状态
            exit(json_encode($return_arr));
        }

        $data['master_order_sn'] = date('YmdHis').rand(1000, 9999);
        D('order')->where(array('order_id' => $order_id))->save($data);
        $return_arr = array('status' => 1, 'msg' => '获取成功', 'result' => $this->getWXPayInfo($order['order_id'])); // 返回结果状态
        exit(json_encode($return_arr));
    }

    public function getWXPayInfo($order_id)
    {
        $o = M('order')->where(array('order_id' => $order_id))->field('user_id,master_order_sn,order_id,order_amount')->find();

        $goods = M('order_goods')->where(array('order_id' => $order_id))->select();

        $orderBody = $goods[0]['goods_name'];

        $goods_num = count($goods);
        if ($goods_num > 1) {
            $orderBody = $orderBody.'等';
        }

        $tade_no = $o['master_order_sn'];

        $user_id = $o['user_id'];
        $open_id = D('users')->where(array('user_id' => $user_id))->getField('open_id');

        $total_fee = $o['order_amount'];
       
        $total_fee = (int)($total_fee * 100);

        $response = $this->getPrePayOrder($orderBody, $tade_no, $total_fee, $open_id);

        $x = $this->getOrder($response['prepay_id']);
        //print_r($x);

        $data1['wdata'] = $x;
        $data1['pay_money'] = $total_fee;

        return $data1;
        //print_r($data1); // 返回新增的订单id
    }

    public $config = array(
        'appid' => 'wxfaa0361fbbdccabd',    /*微信开放平台上的应用id*/
        'mch_id' => '1501164731',   /*微信申请成功之后邮件中的商户id*/
        'api_key' => 'ssHT2018scvIpapI033014w45Q12Ejjj',    /*在微信商户平台上自己设定的api密钥 32位*/
        'notify_url' => 'https://rrd.sshtsc.com/index.php/WXAPI/Pay/wxCallback.html', /*自定义的回调程序地址id*/
    );

    public function getPrePayOrder($body, $out_trade_no, $total_fee, $open_id)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $notify_url = $this->config['notify_url'];

        $onoce_str = $this->getRandChar(32);

        $data['appid'] = $this->config['appid'];
        $data['body'] = $body;
        $data['mch_id'] = $this->config['mch_id'];
        $data['nonce_str'] = $onoce_str;
        $data['notify_url'] = $notify_url;
        $data['out_trade_no'] = $out_trade_no;
        $data['spbill_create_ip'] = $this->get_client_ip();
        $data['total_fee'] = $total_fee;
        $data['trade_type'] = 'JSAPI';
        $data['openid'] = $open_id;
        $s = $this->getSign($data, false);
        $data['sign'] = $s;

        $xml = $this->arrayToXml($data);
        file_put_contents('xml.txt', $xml);
        $response = $this->postXmlCurl($xml, $url);

        //echo $response;
        // 将微信返回的结果xml转成数组
        return $this->xmlstr_to_array($response);
    }

    // 执行第二次签名，才能返回给客户端使用
    public function getOrder($prepayId)
    {
        $data['appId'] = $this->config['appid'];
        $data['nonceStr'] = $this->getRandChar(32);
        $data['package'] = 'prepay_id='.$prepayId;
        $data['signType'] = 'MD5';
        $data['timeStamp'] = time();
        //$data["partnerid"] = $this->config['mch_id'];
        //$data["prepayid"] = $prepayId;

        $s = $this->getSign1($data, false);
        $data['sign'] = $s;
        //$data["prepayid"] = $prepayId;
        return $data;
    }

    // 获取指定长度的随机字符串
    public function getRandChar($length)
    {
        $str = null;
        $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < $length; ++$i) {
            $str .= $strPol[rand(0, $max)]; // rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        return $str;
    }

    /*
     * 生成签名
     */
    public function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[strtolower($k)] = $v;
        }
        // 签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        // echo "【string】 =".$String."</br>";
        // 签名步骤二：在string后加入KEY
        $String = $String.'&key='.$this->config['api_key'];
        // echo "<textarea style='width: 50%; height: 150px;'>$String</textarea> <br />";
        // 签名步骤三：MD5加密
        $result_ = strtoupper(md5($String));

        return $result_;
    }

    /*
     * 生成签名
     */
    public function getSign1($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[strtolower($k)] = $v;
        }
        // 签名步骤一：按字典序排序参数
        ksort($Parameters);
        //$String = $this->formatBizQueryParaMap($Parameters, false);
        $String = 'appId='.$Obj['appId'].'&nonceStr='.$Obj['nonceStr'].'&package='.$Obj['package'].'&signType=MD5&timeStamp='.$Obj['timeStamp'];

        // echo "【string】 =".$String."</br>";
        // 签名步骤二：在string后加入KEY
        $String = $String.'&key='.$this->config['api_key'];
        // echo "<textarea style='width: 50%; height: 150px;'>$String</textarea> <br />";
        // 签名步骤三：MD5加密
        //echo $String;
        $result_ = strtoupper(md5($String));

        return $result_;
    }

    /*
     * 获取当前服务器的IP
     */
    public function get_client_ip()
    {
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv('REMOTE_ADDR')) {
            $cip = getenv('REMOTE_ADDR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $cip = getenv('HTTP_CLIENT_IP');
        } else {
            $cip = 'unknown';
        }

        return $cip;
    }

    // 数组转xml
    public function arrayToXml($arr)
    {
        $xml = '<xml>';

        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= '<'.$key.'>'.$val.'</'.$key.'>';
            } else {
                $xml .= '<'.$key.'><![CDATA['.$val.']]></'.$key.'>';
            }
        }
        $xml .= '</xml>';

        return $xml;
    }

    // post https请求，CURLOPT_POSTFIELDS xml格式
    public function postXmlCurl($xml, $url, $second = 30)
    {
        // 初始化curl
        $ch = curl_init();
        // 超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        // 这里设置代理，如果有的话
        // curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        // curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        // 运行curl
        $data = curl_exec($ch);

        // 返回结果
        if ($data) {
            curl_close($ch);

            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error".'<br>';
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);

            return false;
        }
    }

    /**
     * xml转成数组.
     */
    public function xmlstr_to_array($xmlstr)
    {
        //$doc = new \DOMDocument();
        //$doc->loadXML($xmlstr);
        //return $this->domnode_to_array($doc->documentElement);

        //禁止引用外部xml实体

        libxml_disable_entity_loader(true);

        $xmlstring = simplexml_load_string($xmlstr, 'SimpleXMLElement', LIBXML_NOCDATA);

        $val = json_decode(json_encode($xmlstring), true);

        return $val;
    }

    // 将数组转成uri字符串
    public function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = '';
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= strtolower($k).'='.$v.'&';
        }
        $reqPar;
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }

        return $reqPar;
    }

    public function domnode_to_array($node)
    {
        $output = array();
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i = 0, $m = $node->childNodes->length; $i < $m; ++$i) {
                    $child = $node->childNodes->item($i);
                    $v = $this->domnode_to_array($child);
                    if (isset($child->tagName)) {
                        $t = $child->tagName;
                        if (!isset($output[$t])) {
                            $output[$t] = array();
                        }
                        $output[$t][] = $v;
                    } elseif ($v) {
                        $output = (string) $v;
                    }
                }
                if (is_array($output)) {
                    if ($node->attributes->length) {
                        $a = array();
                        foreach ($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string) $attrNode->value;
                        }
                        $output['@attributes'] = $a;
                    }
                    foreach ($output as $t => $v) {
                        if (is_array($v) && 1 == count($v) && '@attributes' != $t) {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }

        return $output;
    }
}
