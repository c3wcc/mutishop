<?php

namespace WXAPI\Controller;

use WXAPI\Logic\UsersLogic;

class UserController extends BaseController
{
    public $userLogic;

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
        $this->userLogic = new UsersLogic();
    }

    public function test1()
    {
        $accessKeyId = 'LTAIWPvsJMVfnxyj'; //阿里云申请的 Access Key ID
        $accessKeySecret = 'i2rq0vo3cjGz07FeVTZi2FzFNruiNn'; //阿里云申请的 Access Key Secret
        $alisms = new \Org\Util\Alisms($accessKeyId, $accessKeySecret);
        $mobile = '18051228122'; //目标手机号，多个手机号可以逗号分隔
        $code = 'SMS_73720019'; //短信模板的模板CODE
        $paramString = '{"number":"344556"}'; //短信模板中的变量；,参数格式{"no": "123456"}

        $re = $alisms->smsend($mobile, $code, $paramString);
        echo $re;
        print_r($re);
    }

    /**
     * 获取全部地址信息.
     */
    public function getArea()
    {
        $data = M('region')->where(array('parent_id' => $_GET['parent_id'], 'level' => array('neq', 4)))->select();
        $json_arr = array('status' => 1, 'msg' => '成功!', 'result' => $data);
        $json_str = $this->ajaxReturn($json_arr);
        exit($json_str);
    }

    /**
     *生成二维码
     */
    public function createrweima()
    {
        $APPID = C('APPID');
        $APPSECRET = C('APPSECRET');
        $tokenUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$APPID.'&secret='.$APPSECRET;

        $tokenArr = json_decode(httpRequest($tokenUrl, 'GET'));

        $access_token = $tokenArr->access_token;

        if (!$access_token) {
            exit('access_token为空');
        }
        //var_dump($access_token);
        $this->user_id = $_GET['user_id'];
        if (!$this->user_id) {
            exit('user_id不能为空');
        }
        $user_info = M('users')->where("user_id = {$this->user_id}")->find();
        if (!empty($user_info['qrcode'])) {
            $image = 'https://'.$_SERVER['HTTP_HOST'].'/qrcode/'.$user_info['qrcode'];
        } else {
            $path = 'pages/index/index?uid='.$this->user_id;

            $width = 430;
            $post_data = '{"path":"'.$path.'","width":'.$width.',"scene":'.$this->user_id.'}';
            $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;
            $result = $this->api_notice_increment($url, $post_data);
            // var_dump( $result);
            $dir = './qrcode';
            !is_dir($dir) ? mkdir($dir) : null;
            $dir .= '/';
            $jpg = '.jpg';
            $name = time().rand(1111, 9999);
            $target1 = $dir.$name.$jpg;
            $suc = file_put_contents($target1, $result);
            $post['qrcode'] = $name.$jpg;
            $post['is_distribut'] = 1;

            $this->userLogic->update_info($this->user_id, $post);
            $image = 'https://'.$_SERVER['HTTP_HOST'].'/qrcode/'.$name.$jpg;
        }

        $data['result'] = $image;
        exit($image);
    }

    /**
     *  登录.
     */
    public function login()
    {
        $username = I('username', '');
        $password = I('password', '');
        $unique_id = I('unique_id'); // 唯一id  类似于 pc 端的session id
        $data = $this->userLogic->app_login($username, $password);

        if (1 != $data['status']) {
            exit($this->ajaxReturn($data));
        }

        $cartLogic = new \Home\Logic\CartLogic();
        $cartLogic->login_cart_handle($unique_id, $data['result']['user_id']); // 用户登录后 需要对购物车 一些操作
        exit($this->ajaxReturn($data));
    }

    /*
     * 第三方登录
     */
    public function thirdLogin()
    {
        $map['openid'] = I('openid', '');
        $map['oauth'] = I('from', '');
        $map['nickname'] = I('nickname', '');
        $map['head_pic'] = I('head_pic', '');
        $data = $this->userLogic->thirdLogin($map);
        exit($this->ajaxReturn($data));
    }

    /**
     * 用户注册.
     */
    public function reg()
    {
        $username = I('post.username', '');
        $password = I('post.password', '');
        $password2 = I('post.password2', '');
        $unique_id = I('unique_id');
        //是否开启注册验证码机制
        if (check_mobile($username) && TpCache('sms.regis_sms_enable')) {
            $code = I('post.code');
            if (empty($code)) {
                exit($this->ajaxReturn(array('status' => -1, 'msg' => '请输入验证码', 'result' => '')));
            }
            $check_code = $this->userLogic->sms_code_verify($username, $code, $unique_id);
            if (1 != $check_code['status']) {
                exit($this->ajaxReturn(array('status' => -1, 'msg' => $check_code['msg'], 'result' => '')));
            }
        }
        $data = $this->userLogic->reg($username, $password, $password2);
        exit($this->ajaxReturn($data));
    }

    /*
     * 获取用户信息
     */
    public function userInfo()
    {
        //$user_id = I('user_id');
        $data = $this->userLogic->get_info($this->user_id);
        exit($this->ajaxReturn($data));
    }

    /*
     *更新用户信息
     */
    public function updateUserInfo()
    {
        $user_id = I('user_id');
        if (!$user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少uid参数', 'result' => '')));
        }

        if (!$this->userLogic->get_info($user_id)) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '该用户不存在', 'result' => '')));
        }

        I('nick_name') ? $data['nick_name'] = I('nick_name') : false; //昵称
        I('qq') ? $data['qq'] = I('qq') : false;  //QQ号码
        I('head_pic') ? $data['head_pic'] = I('head_pic') : false; //头像地址
        I('sex') ? $data['sex'] = I('sex') : false;  // 性别
        I('gender') ? $data['gender'] = I('gender') : false;  // 性别
        I('birthday') ? $data['birthday'] = strtotime(I('birthday')) : false;  // 生日
        I('country') ? $data['country'] = I('country') : false;  //地区
        I('province') ? $data['province'] = I('province') : false;  //省份
        I('city') ? $data['city'] = I('city') : false;  // 城市
        I('district') ? $data['district'] = I('district') : false;  //地区

        $head_pic = $_GET['head_pic'];
        if ($head_pic) {
            $this->test($data['head_pic'], $user_id);
        }

        if (!$this->userLogic->update_info($user_id, $data)) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '更新失败', 'result' => '')));
        }

        exit($this->ajaxReturn(array('status' => 1, 'msg' => '更新成功', 'result' => '')));
    }

    /*
     * 修改用户密码
     */
    public function password()
    {
        if (IS_POST) {
            //$user_id = I('user_id');
            if (!$this->user_id) {
                exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => '')));
            }
            $data = $this->userLogic->password($this->user_id, I('post.old_password'), I('post.new_password'), I('post.confirm_password')); // 获取用户信息
            exit($this->ajaxReturn($data));
        }
    }

    /**
     * 获取收货地址
     */
    public function getAddressList()
    {
        /*"province": "338",
            "city": "569",
            "district": "586",*/
        $this->user_id = I('user_id');
        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => '')));
        }
        $address = M('user_address')->where(array('user_id' => $this->user_id))->select();
        foreach ($address as $key => $value) {
            $address[$key]['address'] = M('region')->where(array('id' => $value['province']))->getField('name').M('region')->where(array('id' => $value['city']))->getField('name').M('region')->where(array('id' => $value['district']))->getField('name').$address[$key]['address'];
        }

        if (!$address) {
            exit($this->ajaxReturn(array('status' => 1, 'msg' => '没有数据', 'result' => '')));
        }
        exit($this->ajaxReturn(array('status' => 1, 'msg' => '获取成功', 'result' => $address)));
    }

    /*
     * 添加地址
     */
    public function addAddress()
    {
        $object = file_get_contents('php://input');
        $_POST = (json_decode($object, true));

        $this->user_id = $_POST['user_id'];
        //echo $this->user_id.'1';
        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => '')));
        }
        $address_id = I('address_id', 0);
        $data = $this->userLogic->add_address($this->user_id, $address_id, I('post.')); // 获取用户信息
        exit($this->ajaxReturn($data));
    }

    /*
    * 添加体现
    */
    public function addWithdraw()
    {
        $object = file_get_contents('php://input');
        $_POST = (json_decode($object, true));

        $this->user_id = $_POST['user_id'];
        //echo $this->user_id.'1';
        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => '')));
        }
        $address_id = I('address_id', 0);
        $data = $this->userLogic->add_Withdraw($this->user_id, $address_id, I('post.')); // 获取用户信息
        exit($this->ajaxReturn($data));
    }

    /*
     * 编辑地址
     */
    public function editAddress()
    {
        //echo $address_id;
        $object = file_get_contents('php://input');
        $_POST = (json_decode($object, true));

        $address_id = $_POST['address_id'];
        $this->user_id = $_POST['user_id'];
        //echo $this->user_id.'1';
        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => '')));
        }

        //echo $address_id;

        //echo $_POST['address'];
        M('user_address')->where(array('address_id' => $address_id))->save(array('address' => $_POST['address'], 'mobile' => $_POST['mobile'], 'zipcode' => $_POST['zipcode'], 'consignee' => $_POST['consignee'], 'province' => $_POST['province'], 'city' => $_POST['city'], 'district' => $_POST['district']));
        exit($this->ajaxReturn(array('status' => 1, 'msg' => '成功', 'result' => '')));
    }

    /*
     * 地址删除
     */
    public function del_address()
    {
        $id = I('id');
        $this->user_id = I('user_id');
        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => '')));
        }
        $address = M('user_address')->where("address_id = $id")->find();
        $row = M('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if (1 == $address['is_default']) {
            $address = M('user_address')->where("user_id = {$this->user_id}")->find();
            M('user_address')->where("address_id = {$address['address_id']}")->save(array('is_default' => 1));
        }
        if ($row) {
            exit($this->ajaxReturn(array('status' => 1, 'msg' => '删除成功', 'result' => '')));
        } else {
            exit($this->ajaxReturn(array('status' => 1, 'msg' => '删除失败', 'result' => '')));
        }
    }

    /*
     * 地址删除
     */
    public function get_address()
    {
        $id = I('id');
        $this->user_id = I('user_id');
        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => '')));
        }
        $address = M('user_address')->where("address_id = $id")->find();
        $address['cityvalue'] = $address['city'];
        $address['city'] = M('region')->where(array('id' => $address['province']))->getField('name').M('region')->where(array('id' => $address['city']))->getField('name').M('region')->where(array('id' => $address['district']))->getField('name');

        exit($this->ajaxReturn(array('status' => 1, 'msg' => '删除失败', 'result' => $address)));
    }

    /*
     * 设置默认收货地址
     */
    public function setDefaultAddress()
    {
//        $user_id = I('user_id',0);
        $this->user_id = I('user_id', 0);
        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => '')));
        }
        $address_id = I('address_id', 0);
        $data = $this->userLogic->set_default($this->user_id, $address_id); // 获取用户信息
        if (!$data) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '操作失败', 'result' => '')));
        }
        exit($this->ajaxReturn(array('status' => 1, 'msg' => '操作成功', 'result' => '')));
    }

    /*
     * 获取优惠券列表
     */
    public function getCouponList()
    {
        $this->user_id = I('user_id', 0);
        $p = I('page', 0);
        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '参数有误', 'result' => '')));
        }
        $data = $this->userLogic->get_coupon($this->user_id, $_REQUEST['type'], $p);

        foreach ($data['result'] as $k => $v) {
            $data['result'][$k]['use_end_time'] = date('y-m-h h:m:s', $v['use_end_time']);
        }

        unset($data['show']);
        exit($this->ajaxReturn($data));
    }

    /*
     * 获取商品收藏列表
     */
    public function getGoodsCollect()
    {
        $this->user_id = I('user_id', 0);
        $page = I('page', 0);
        //if(!$this->user_id) exit($this->ajaxReturn(array('status'=>-1,'msg'=>'缺少参数','result'=>'')));
        $data = $this->userLogic->get_goods_collect($this->user_id, $page);
        foreach ($data['result'] as $key => $value) {
            $data['result'][$key]['image'] = SITE_URL.$value['original_img'];
        }
        unset($data['show']);
        exit($this->ajaxReturn($data));
    }

    /*
     * 用户订单列表
     */
    public function getOrderList()
    {
        $this->user_id = I('user_id', 0);
        $type = I('type', '');

        if ('NO' == $type) {
            $type = '';
        }

        $page = I('page', '');
        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => '')));
        }
        //条件搜索
        //I('field') && $map[I('field')] = I('value');
        //I('type') && $map['type'] = I('type');
        //$map['user_id'] = $user_id;
        $map = " user_id = {$this->user_id} ";
        $map = $type ? $map.C($type) : $map;

        //echo 1;
        //print_r($map);

        if (I('type')) {
            $count = M('order')->where($map)->count();
        }
        $Page = new \Think\Page($count, 10);

        $show = $Page->show();
        $order_str = 'order_id DESC';
        $Page->firstRow = $Page->listRows * $page;
        //echo $page;
        //echo $Page->firstRow;
        $order_list = M('order')->order($order_str)->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();

        //获取订单商品
        foreach ($order_list as $k => $v) {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            //订单总额
            //$order_list[$k]['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['integral_money'] -$v['bonus'] - $v['discount'];
            $data = $this->userLogic->get_order_goods($v['order_id']);

            foreach ($data['result'] as $key => $value) {
                $data['result'][$key]['image'] = SITE_URL.$value['original_img'];
            }

            $order_list[$k]['goods_list'] = $data['result'];
        }
        exit($this->ajaxReturn(array('status' => 1, 'msg' => '获取成功', 'result' => $order_list)));
    }

    /*
    * 用户推广订单列表
    */
    public function getShareOrderList()
    {
        $this->user_id = I('user_id', 0);
        $type = I('type', '');

        if ('NO' == $type) {
            $type = '';
        }

        $page = I('page', '');
        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => '')));
        }
        //条件搜索
        //I('field') && $map[I('field')] = I('value');
        //I('type') && $map['type'] = I('type');
        //$map['user_id'] = $user_id;
        $map = " (first_leader = {$this->user_id} or second_leader = {$this->user_id} or third_leader = {$this->user_id}) ";
        $map = $type ? $map.C($type) : $map;

        //echo 1;

        if (I('type')) {
            $count = M('order')->where($map)->count();
        }

        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $order_str = 'order_id DESC';
        $Page->firstRow = $Page->listRows * $page;
        //echo $page;
        //echo $Page->firstRow;
        $order_list = M('order')->order($order_str)->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();

        //获取订单商品
        foreach ($order_list as $k => $v) {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            //订单总额
            //$order_list[$k]['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['integral_money'] -$v['bonus'] - $v['discount'];
            $data = $this->userLogic->get_order_goods($v['order_id']);

            foreach ($data['result'] as $key => $value) {
                $data['result'][$key]['image'] = SITE_URL.$value['original_img'];
            }

            $order_list[$k]['goods_list'] = $data['result'];
        }
        exit($this->ajaxReturn(array('status' => 1, 'msg' => '获取成功', 'result' => $order_list)));
    }

    /*
     * 用户推广会员列表
     */
    public function getShareList()
    {
        $this->user_id = I('user_id', 0);
        $type = I('type', '');

        // if($type == "NO")
        // $type = "";

        $page = I('page', '');
        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => '')));
        }
        //条件搜索
        //I('field') && $map[I('field')] = I('value');
        //I('type') && $map['type'] = I('type');
        //$map['user_id'] = $user_id;

        if (1 == $type) {
            $map = " first_leader = {$this->user_id} ";
        } elseif (2 == $type) {
            $map = " second_leader = {$this->user_id} ";
        } elseif (3 == $type) {
            $map = " third_leader = {$this->user_id} ";
        } else {
        }
        //$map = $type ? $map.C($type) : $map;

        //echo 1;
        //print_r($map);

        if (I('type')) {
            $count = M('users')->where($map)->count();
        }
        $Page = new \Think\Page($count, 10);

        $show = $Page->show();
        $order_str = 'user_id DESC';
        $Page->firstRow = $Page->listRows * $page;
        //echo $page;
        //echo $Page->firstRow;
        $member_list = M('users')->order($order_str)->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($member_list as $key => $value) {
             if(strpos($member_list[$key]['head_pic'],'http' ) !== false  ){
               
             }else{
                $member_list[$key]['head_pic'] = 'https://'.$_SERVER['SERVER_NAME'].$value['head_pic'];
             }
           
            //$list[$key]['end_time'] = date("Y-m-d H-i-s",$value['end_time']);
        }

        exit($this->ajaxReturn(array('status' => 1, 'msg' => '获取成功', 'result' => $member_list)));
    }

    /*
     * 获取订单详情
     */
    public function getOrderDetail()
    {
        $this->user_id = I('user_id', 0);
        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '缺少参数', 'result' => '')));
        }
        $id = I('id');
        if (I('id')) {
            $map['order_id'] = $id;
        }

        if (I('sn')) {
            $map['order_sn'] = I('sn');
        }

        $map['user_id'] = $this->user_id;

        $order_info = M('order')->where($map)->find();
        $order_info = set_btn_order_status($order_info);  // 添加属性  包括按钮显示属性 和 订单状态显示属性

        if (!$this->user_id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '参数有误', 'result' => '')));
        }
        if (!$order_info) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '订单不存在', 'result' => '')));
        }

        $invoice_no = M('DeliveryDoc')->where(array('order_id' => $id))->getField('invoice_no');
        $order_info['invoice_no'] = implode(' , ', $invoice_no);

        // 获取 最新的 一次发货时间
        $order_info['shipping_time'] = M('DeliveryDoc')->where(array('order_id' => $id))->order('id desc')->getField('create_time');

        $order_info['store_name'] = M('store')->where(array('store_id' => $order_info['store_id']))->getField('store_name');
        //获取订单商品
        $data = $this->userLogic->get_order_goods($order_info['order_id']);

        foreach ($data['result'] as $key => $value) {
            $data['result'][$key]['image'] = SITE_URL.$value['original_img'];
        }
        $order_info['goods_list'] = $data['result'];
        //$order_info['total_fee'] = $order_info['goods_price'] + $order_info['shipping_price'] - $order_info['integral_money'] -$order_info['coupon_price'] - $order_info['discount'];
        exit($this->ajaxReturn(array('status' => 1, 'msg' => '获取成功', 'result' => $order_info)));
    }

    /**
     * 取消订单.
     */
    public function cancelOrder()
    {
        $id = I('order_id');
        $this->user_id = I('user_id');
        if (!$this->user_id  || !$id ) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '参数有误', 'result' => '')));
        }
        $data = $this->userLogic->cancel_order($this->user_id, $id);
        exit($this->ajaxReturn($data));
    }

    /**
     * 发送手机注册验证码
     * http://www.tp-shop.cn/index.php?m=Api&c=User&a=send_sms_reg_code&mobile=13800138006&unique_id=123456.
     */
    public function send_sms_reg_code()
    {
        $mobile = I('mobile');
        $user_id = I('user_id');
        if (!check_mobile($mobile)) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '手机号码格式有误')));
        }
        $code = rand(1000, 9999);
        $send = $this->userLogic->sms_log($mobile, $code, $user_id);
        if (1 != $send['status']) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => $send['msg'])));
        }
        exit($this->ajaxReturn(array('status' => 1, 'msg' => '验证码已发送，请注意查收')));
    }

    /**
     *  收货确认.
     */
    public function orderConfirm()
    {
        $id = I('order_id', 0);
        $this->user_id = I('user_id', 0);
        if (!$this->user_id || !$id) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '参数有误', 'result' => '')));
        }
        $data = confirm_order($id, $this->user_id);
        exit($this->ajaxReturn($data));
    }

    public function parentname()
    {
        $this->user_id = I('user_id', 0);
        $user_info = M('users')->where("user_id = {$this->user_id}")->find();
        if ($user_info['first_leader'] > 0) {
            $parent_info = M('users')->where("user_id = '".$user_info['first_leader']."'")->find();
            exit($this->ajaxReturn(array('status' => 1, 'msg' => '', 'result' => $parent_info['nick_name'])));
        } else {
            exit($this->ajaxReturn(array('status' => 1, 'msg' => '', 'result' => '无推荐人')));
        }
    }

    public function comments()
    {
        /*
    	 *
    	 *
    	 *$user_id = $this->user_id;
        $status = I('get.status');
        $logic = new UsersLogic();
        $result = $logic->get_comment($user_id, $status); //获取评论列表
        $this->assign('comment_list', $result['result']);
        if ($_GET['is_ajax']) {
            $this->display('ajax_comment_list');
            exit;
        }
        $this->display();
    	 */
        $this->user_id = I('user_id', 0);
        $p = I('page', 0);

        $status = I('get.status');
        $logic = new UsersLogic();
        $result = $logic->get_comment($this->user_id, $status, $p); //获取评论列表

        //$count = M('comment')->where(array("user_id"=>$this->user_id))->count();
        //$page = new \Think\Page($count,10);
        //$page->firstRow = $page->listRows * $p;
        $datas = $result['result']; //M('comment')->where(array("user_id"=>$this->user_id))->limit("{$page->firstRow},{$page->listRows}")->select();

        foreach ($datas as $key => $value) {
            $datas[$key] = array_merge(M('goods')->where(array('goods_id' => $value['goods_id']))->find(), $value);
            $datas[$key]['image'] = SITE_URL.$datas[$key]['original_img'];
            $datas[$key]['add_time'] = date('Y-m-d H:i:s', $datas[$key]['add_time']);
            $comment = M('comment')->where(array('goods_id' => $datas[$key]['goods_id'], 'order_id' => $datas[$key]['order_id']))->find();
            if ($comment) {
                $datas[$key]['service_rank'] = $comment['service_rank'];
            }
        }

        if (!$datas) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '操作失败', 'result' => '')));
        }
        exit($this->ajaxReturn(array('status' => 1, 'msg' => '操作成功', 'result' => $datas)));
    }

    /*
     *添加评论
     */
    public function add_comment()
    {
        // 晒图片
        if ($_FILES[img_file][tmp_name][0]) {
            $upload = new \Think\Upload(); // 实例化上传类
                    $upload->maxSize = $map['author'] = (1024 * 1024 * 3); // 设置附件上传大小 管理员10M  否则 3M
                    $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                    $upload->rootPath = './Public/upload/comment/'; // 设置附件上传根目录
                    $upload->replace = true; // 存在同名文件是否是覆盖，默认为false
                    //$upload->saveName  =   'file_'.$id; // 存在同名文件是否是覆盖，默认为false
                    // 上传文件
                    $info = $upload->upload();
            if (!$info) {// 上传错误提示错误信息
                        exit($this->ajaxReturn(array('status' => -1, 'msg' => $upload->getError()))); //$this->error($upload->getError());
            } else {
                foreach ($info as $key => $val) {
                    $comment_img[] = '/Public/upload/comment/'.$val['savepath'].$val['savename'];
                }
                $comment_img = serialize($comment_img); // 上传的图片文件
            }
        }

        //$unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
            $this->user_id = I('user_id'); // 用户id
            $user_info = M('users')->where("user_id = {$this->user_id}")->find();

        $add['goods_id'] = I('goods_id');
        $add['email'] = $user_info['email'];
        //$add['nick'] = $user_info['nickname'];
        $add['username'] = $user_info['nickname'];
        $add['order_id'] = I('order_id');
        $add['service_rank'] = I('service_rank');
        $add['deliver_rank'] = I('deliver_rank');
        $add['goods_rank'] = I('goods_rank');
        // $add['content'] = htmlspecialchars(I('post.content'));
        $add['content'] = I('content');
        $add['img'] = $comment_img;
        $add['add_time'] = time();
        $add['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $add['user_id'] = $this->user_id;

        //添加评论
        $row = $this->userLogic->add_comment($add);
        exit($this->ajaxReturn($row));
    }

    /*
     * 账户余额
     */
    public function user_money()
    {
        $user_id = I('user_id'); // 用户id
        $data['user_money'] = M('users')->where(array('user_id' => $user_id))->getField('user_money');
        exit($this->ajaxReturn(array('status' => 1, 'msg' => '获取成功', 'result' => $data)));
    }

    /*
     * 账户资金
     */
    public function account()
    {
        $this->user_id = I('user_id'); // 唯一id  类似于 pc 端的session id
        // $user_id = I('user_id'); // 用户id
        //获取账户资金记录
        $page = I('page', 0);
        $data = $this->userLogic->get_account_log($this->user_id, I('get.type'), $page);
        $account_log = $data['result'];

        foreach ($account_log as $key => $value) {
            $account_log[$key]['change_time'] = date('Y-m-d h:i:s', $value['change_time']);
        }

        exit($this->ajaxReturn(array('status' => 1, 'msg' => '获取成功', 'result' => $account_log)));
    }

    /*
    * 提现明细
    */
    public function withdrawlist()
    {
        $this->user_id = I('user_id'); // 唯一id  类似于 pc 端的session id
        // $user_id = I('user_id'); // 用户id
        //获取账户资金记录
        $page = I('page', 0);
        $data = $this->userLogic->get_withdrawlist($this->user_id, I('get.type'), $page);
        $account_log = $data['result'];

        foreach ($account_log as $key => $value) {
            $account_log[$key]['change_time'] = date('Y-m-d h:i:s', $value['create_time']);
            if (0 == $value['status']) {
                $account_log[$key]['desc'] = '审核中';
            } else {
                $account_log[$key]['desc'] = '审核通过，已打款';
            }
        }

        exit($this->ajaxReturn(array('status' => 1, 'msg' => '获取成功', 'result' => $account_log)));
    }

    /**
     * 退换货列表.
     */
    public function return_goods_list()
    {
        $unique_id = I('unique_id'); // 唯一id  类似于 pc 端的session id
        // $user_id = I('user_id'); // 用户id
        $count = M('return_goods')->where("user_id = {$this->user_id}")->count();
        $page = new \Think\Page($count, 4);
        $list = M('return_goods')->where("user_id = {$this->user_id}")->order('id desc')->limit("{$page->firstRow},{$page->listRows}")->select();
        $goods_id_arr = get_arr_column($list, 'goods_id');
        if (!empty($goods_id_arr)) {
            $goodsList = M('goods')->where('goods_id in ('.implode(',', $goods_id_arr).')')->getField('goods_id,goods_name');
        }
        foreach ($list as $key => $val) {
            $val['goods_name'] = $goodsList[$val[goods_id]];
            $list[$key] = $val;
        }
        //$this->assign('page', $page->show());// 赋值分页输出
        exit($this->ajaxReturn(array('status' => 1, 'msg' => '获取成功', 'result' => $list)));
    }

    /**
     *  售后 详情.
     */
    public function return_goods_info()
    {
        $id = I('id', 0);
        $return_goods = M('return_goods')->where("id = $id")->find();
        if ($return_goods['imgs']) {
            $return_goods['imgs'] = explode(',', $return_goods['imgs']);
        }
        $goods = M('goods')->where("goods_id = {$return_goods['goods_id']} ")->find();
        $return_goods['goods_name'] = $goods['goods_name'];
        exit($this->ajaxReturn(array('status' => 1, 'msg' => '获取成功', 'result' => $return_goods)));
    }

    /**
     * 申请退货状态
     */
    public function return_goods_status()
    {
        $order_id = I('order_id', 0);
        $goods_id = I('goods_id', 0);
        $spec_key = I('spec_key', '');

        $return_goods = M('return_goods')->where("order_id = $order_id and goods_id = $goods_id and spec_key = '$spec_key' and status in(0,1)")->find();
        if (!empty($return_goods)) {
            exit($this->ajaxReturn(array('status' => 1, 'msg' => '已经在申请退货中..', 'result' => $return_goods['id'])));
        } else {
            exit($this->ajaxReturn(array('status' => 1, 'msg' => '可以去申请退货', 'result' => -1)));
        }
    }

    /**
     * 申请退货.
     */
    public function return_goods()
    {
        $unique_id = I('unique_id'); // 唯一id  类似于 pc 端的session id
        //$user_id = I('user_id'); // 用户id
        $order_id = I('order_id', 0);
        $order_sn = I('order_sn', 0);
        $goods_id = I('goods_id', 0);
        $type = I('type', 0); // 0 退货  1为换货
        $reason = I('reason', ''); // 问题描述
        $spec_key = I('spec_key');

        if (empty($order_id) || empty($order_sn) || empty($goods_id) || empty($this->user_id) || empty($type) || empty($reason)) {
            exit($this->ajaxReturn(array('status' => -1, 'msg' => '参数不齐!')));
        }

        $c = M('order')->where("order_id = $order_id and user_id = {$this->user_id}")->count();
        if (0 == $c) {
            exit($this->ajaxReturn(array('status' => -3, 'msg' => '非法操作!')));
        }

        $return_goods = M('return_goods')->where("order_id = $order_id and goods_id = $goods_id and spec_key = '$spec_key' and status in(0,1)")->find();
        if (!empty($return_goods)) {
            exit($this->ajaxReturn(array('status' => -2, 'msg' => '已经提交过退货申请!')));
        }
        if (IS_POST) {
            // 晒图片
            if ($_FILES[img_file][tmp_name][0]) {
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = $map['author'] = (1024 * 1024 * 3); // 设置附件上传大小 管理员10M  否则 3M
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                $upload->rootPath = './Public/upload/return_goods/'; // 设置附件上传根目录
                $upload->replace = true; // 存在同名文件是否是覆盖，默认为false
                //$upload->saveName  =  'file_'.$id; // 存在同名文件是否是覆盖，默认为false
                // 上传文件
                $upinfo = $upload->upload();
                if (!$upinfo) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                } else {
                    foreach ($upinfo as $key => $val) {
                        $return_imgs[] = '/Public/upload/return_goods/'.$val['savepath'].$val['savename'];
                    }
                    $data['imgs'] = implode(',', $return_imgs); // 上传的图片文件
                }
            }
            $data['order_id'] = $order_id;
            $data['order_sn'] = $order_sn;
            $data['goods_id'] = $goods_id;
            $data['addtime'] = time();
            $data['user_id'] = $this->user_id;
            $data['type'] = $type; // 服务类型  退货 或者 换货
            $data['reason'] = $reason; // 问题描述
            $data['spec_key'] = $spec_key; // 商品规格
            M('return_goods')->add($data);
            exit($this->ajaxReturn(array('status' => 1, 'msg' => '申请成功,客服第一时间会帮你处理!')));
        }
    }

    public function validateOpenid()
    {
        $open_id = $_GET['openid'];
        if (!$open_id) {
            $this->ajaxReturn(array('code' => '400', 'msg' => 'openid不能为空'));
            exit;
        }

        $res = M('users')->where(array('open_id' => $open_id))->find();

        if ($res) {
            $res['head_pic'] = SITE_URL.$res['head_pic'];
            $tp_config = M('config')->where(array('name' => 'hot_keywords'))->find();

            //$res['apply'] = D('seller_apply')->where(array("user_id"=>$res['user_id']))->find();

            if ('hot_keywords' == $tp_config['name']) {
                $res['hot_keywords'] = explode('|', $tp_config['value']);
            }

            $this->ajaxReturn(array('code' => '200', 'msg' => '验证成功', 'data' => $res));
        } else {
            $this->ajaxReturn(array('code' => '400', 'msg' => '验证失败'));
        }
    }

    public function bindPhone()
    {
        $user_id = $_GET['user_id'];
        if (!$user_id) {
            $this->ajaxReturn(array('code' => '-1', 'msg' => 'user_id不能为空'));
            exit;
        }
        $open_id = $_GET['open_id'];
        if (!$open_id) {
            $this->ajaxReturn(array('code' => '-1', 'msg' => 'open_id不能为空'));
            exit;
        }
        $user = M('users')->where(array('user_id' => $user_id, 'open_id' => $open_id))->find();
        if (!$user) {
            $this->ajaxReturn(array('code' => '-1', 'msg' => '用户不存在或user_id与openid不匹配'));
            exit;
        }
        $phoneNum = $_GET['phone'];
        if (!$phoneNum) {
            $this->ajaxReturn(array('code' => '-1', 'msg' => '手机号码phone不能为空'));
            exit;
        }
        if (11 !== strlen($phoneNum)) {
            $this->ajaxReturn(array('code' => '-1', 'msg' => '手机号码phone长度不正确'));
            exit;
        }

        $res = M('users')->where(array('user_id' => $user_id))->save(array('mobile' => $phoneNum, 'mobile_validated' => 1));
        $this->ajaxReturn(array('code' => '1', 'msg' => '手机号码绑定成功'));
    }

    /**
     * 分销
     * （1.0.9以后的小程序，可去掉）
     * 
     */
    public function distribution()
    {
        $user_id = $_GET['user_id'];
        //下级
        //首先确保这个用户，的first_leader不能为空为0
        $first = M('users')->where(array('user_id' => $user_id))->getField('first_leader');
        if ($first > 0) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '已经存在上级'));
            exit;
        }

        $first_leader = (int) $_GET['first_leader'];
        if (0 == $first_leader) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '上级first_leader不能为空'));
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

    /**
     * 用手机号码，验证码
     * 注册.
     */
    public function code_register()
    {
        $data['open_id'] = $_GET['open_id'];
        if (!$data['open_id'] || null == $data['open_id'] || 'undefined' == $data['open_id']) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '注册失败，openid_id不能为空'));
            exit;
        }
        $code = $_GET['code'];
        if (!$code) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '验证码不能为空'));
            exit;
        }
        $data['mobile'] = $_GET['mobile'];
        if (!$data['mobile']) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '手机号mobile不能为空'));
            exit;
        }
        //验证是否一致
        $sms = M('sms_log')->where(array('mobile' => $data['mobile']))->order('id DESC')->find();
        if ($sms['code'] != $code) {
            $this->ajaxReturn(array('code' => 400, 'msg' => '手机号与验证码不匹配'));
            exit;
        }

        $data['mobile_validated'] = 1;

        $data['reg_time'] = time();

        $nick_name = $_GET['nick_name'];

        $cz = M('users')->where(array('open_id' => $data['open_id']))->find();
        if ($cz) {
            //如果该用户已存在,修改手机号码
            M('users')->where(array('open_id' => $data['open_id']))->save(array('mobile' => $data['mobile'], 'mobile_validated' => 1));
            $cz['user_id'] = $id;
            $this->ajaxReturn(array('code' => '200', 'msg' => '注册成功', 'res' => $res));
            exit;
        }

        $id = M('users')->add($data);
        $res = M('users')->where(array('user_id' => $id))->find();

        if ($res) {
            $tp_config = M('config')->where(array('name' => 'hot_keywords'))->find();
            if ('hot_keywords' == $tp_config['name']) {
                $res['hot_keywords'] = explode('|', $tp_config['value']);
            }

            //$res['apply'] = D('seller_apply')->where(array("user_id"=>$res['user_id']))->find();

            $res['user_id'] = $id;
            $this->ajaxReturn(array('code' => '200', 'msg' => '注册成功', 'res' => $res));
        } else {
            $this->ajaxReturn(array('code' => '400', 'msg' => '注册失败'));
        }
    }

    public function register()
    {
        $data['city'] = $_GET['city'];
        $data['country'] = $_GET['country'];
        $data['gender'] = $_GET['gender'];
        $data['open_id'] = $_GET['open_id'];
        $data['nick_name'] = $_GET['nick_name'];
        $data['province'] = $_GET['province'];
        $data['head_pic'] = $_GET['head_pic'];
        $data['first_leader'] = $_GET['first_leader'];
        if ($data['first_leader'] > 0) {
            $res = M('users')->where(array('user_id' => $data['first_leader']))->find();
            $data['second_leader'] = $res['first_leader'];
            $data['third_leader'] = $res['second_leader'];
        }
        $data['reg_time'] = time();

        if (!$data['open_id'] || null == $data['open_id'] || 'undefined' == $data['open_id']) {
            $this->ajaxReturn(array('code' => '400', 'msg' => '注册失败，openid_id不能为空'));
            exit;
        }
        if (!$data['nick_name'] || null == $data['nick_name'] || 'undefined' == $data['nick_name']) {
            $this->ajaxReturn(array('code' => '400', 'msg' => '注册失败，nick_name不能为空'));
            exit;
        }

        $cz = M('users')->where(array('open_id' => $data['open_id']))->find();
        if ($cz) {
            $this->ajaxReturn(array('code' => '400', 'msg' => '注册失败，该用户已经存在'));
            exit;
        }

        $id = M('users')->add($data);
        $res = M('users')->where(array('user_id' => $id))->find();

        $this->test($data['head_pic'], $id);

        if ($res) {
            $tp_config = M('config')->where(array('name' => 'hot_keywords'))->find();

            if ('hot_keywords' == $tp_config['name']) {
                $res['hot_keywords'] = explode('|', $tp_config['value']);
            }

            //$res['apply'] = D('seller_apply')->where(array("user_id"=>$res['user_id']))->find();

            $this->ajaxReturn(array('code' => '200', 'msg' => '注册成功', 'res' => $res));
        } else {
            $this->ajaxReturn(array('code' => '400', 'msg' => '失败'));
        }
    }

    public function test($url, $id)
    {
        $header = array('Connection: Keep-Alive', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Pragma: no-cache', 'Accept-Language: zh-Hans-CN,zh-Hans;q=0.8,en-US;q=0.5,en;q=0.3', 'User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:29.0) Gecko/20100101 Firefox/29.0');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, $v);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $content = curl_exec($ch);

        $curlinfo = curl_getinfo($ch);

        //echo "string";

        //print_r($curlinfo);

        //关闭连接

        curl_close($ch);

        if (200 == $curlinfo['http_code']) {
            if ('image/jpeg' == $curlinfo['content_type']) {
                $exf = '.jpg';
            } elseif ('image/png' == $curlinfo['content_type']) {
                $exf = '.png';
            } elseif ('image/gif' == $curlinfo['content_type']) {
                $exf = '.gif';
            }

            //存放图片的路径及图片名称  *****这里注意 你的文件夹是否有创建文件的权限 chomd -R 777 mywenjian

            $filename = 'Public/head/'.$id.$exf; //这里默认是当前文件夹，可以加路径的 可以改为$filepath = '../'.$filename

            $res = file_put_contents($filename, $content); //同样这里就可以改为$res = file_put_contents($filepath, $content);

            $filename = '/'.$filename;

            M('users')->where(array('user_id' => $id))->save(array('head_pic' => $filename));
        }
    }

    public function getHotKeywords()
    {
        $tp_config = M('config')->where(array('name' => 'hot_keywords'))->find();

        if ('hot_keywords' == $tp_config['name']) {
            $res['hot_keywords'] = explode('|', $tp_config['value']);
        }

        $this->ajaxReturn(array('code' => '200', 'msg' => '验证成功', 'data' => $res));
    }

    public function logoutWX()
    {
        $open_id = $_GET['openid'];

        if (null == $open_id) {
            $this->ajaxReturn(array('code' => '400', 'msg' => '非法参数'));
            exit();
        }

        $res = 1; ///D('phone_captcha')->where(array("phone"=>$phone,'captcha'=>$num))->find();
        if ($res) {
            $res = M('users')->where(array('open_id' => $open_id))->save(array('open_id' => '', 'openid' => '', 'oauth' => ''));

            if ($res) {
                $this->ajaxReturn(array('code' => '200', 'msg' => '注销成功', 'res' => $res));
            } else {
                $this->ajaxReturn(array('code' => '400', 'msg' => '注销有误,请稍后重试'));
            }
        } else {
            $this->ajaxReturn(array('code' => '400', 'msg' => '验证码或者手机号码有误'));
        }
    }

    public function validate()
    {
        $phone = $_GET['phone'];
        $num = $_GET['num'];
        $open_id = $_GET['openid'];

        if (null == $open_id) {
            $this->ajaxReturn(array('code' => '400', 'msg' => '非法参数'));
            exit();
        }
        if (null == $phone || 11 != strlen($phone)) {
            $this->ajaxReturn(array('code' => '400', 'msg' => '手机号码输入有误'));
            exit();
        }

        $res = 1; ///D('phone_captcha')->where(array("phone"=>$phone,'captcha'=>$num))->find();
        if ($res) {
            $res = M('users')->where(array('mobile' => $phone))->save(array('open_id' => $open_id, 'openid' => $open_id, 'oauth' => 'weixin'));

            if ($res) {
                $res = M('users')->where(array('open_id' => $open_id))->find();
                $this->ajaxReturn(array('code' => '200', 'msg' => '登录成功', 'res' => $res));
            } else {
                $this->ajaxReturn(array('code' => '400', 'msg' => '手机号码有误'));
            }
        } else {
            $this->ajaxReturn(array('code' => '400', 'msg' => '验证码或者手机号码有误'));
        }
    }

    public function register1()
    {
        $phone = $_GET['phone'];
        $num = $_GET['num'];
        $user_id = $_GET['user_id'];
        $pass = $_GET['pass'];
        $remindpass = $_GET['remindpass'];

        if (null == $phone || 11 != strlen($phone)) {
            $this->ajaxReturn(array('code' => '400', 'msg' => '手机号码输入有误'));
            exit();
        }
        $res = M('sms_log')->where(array('mobile' => $phone, 'code' => $num))->find();
        if (!$res) {
            $this->ajaxReturn(array('code' => '400', 'msg' => '验证码输入有误'));
            exit();
        }

        $data['mobile'] = $phone;
        $data['nickname'] = $pass;
        $data['email'] = $remindpass;

        $res = M('users')->where(array('user_id' => $user_id))->save($data);
        $res = M('users')->where(array('user_id' => $user_id))->find();
        if ($res) {
            $this->ajaxReturn(array('code' => '200', 'msg' => '完善成功', 'res' => $res));
        } else {
            $this->ajaxReturn(array('code' => '400', 'msg' => '失败'));
        }
    }

    public function points()
    {
        $this->user_id = I('user_id');
        $p = I('page', 0);
        $type = I('type', 'all');
        $this->assign('type', $type);
        if ('recharge' == $type) {
            $count = M('recharge')->where('user_id='.$this->user_id)->count();
            $Page = new Page($count, 16);
            $Page->firstRow = $p * $Page->listRows;
            $account_log = M('recharge')->where('user_id='.$this->user_id)->order('order_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        } elseif ('points' == $type) {
            $count = M('account_log')->where('user_id='.$this->user_id.' and pay_points!=0 ')->count();
            $Page = new \Think\Page($count, 10);
            $Page->firstRow = $p * $Page->listRows;
            $account_log = M('account_log')->where('user_id='.$this->user_id.' and pay_points!=0 ')->order('log_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        } else {
            $count = M('account_log')->where('user_id='.$this->user_id)->count();
            $Page = new Page($count, 16);
            $Page->firstRow = $p * $Page->listRows;
            $account_log = M('account_log')->where('user_id='.$this->user_id)->order('log_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        }
        foreach ($account_log as $key => $value) {
            $account_log[$key]['change_time'] = date('Y-m-d h-i-s', $value['change_time']);
        }
        $this->ajaxReturn(array('code' => '200', 'msg' => '成功', 'res' => $account_log));
    }

    /**
     * 商店入驻.
     */
    public function store_apply()
    {
        $_GET['add_time'] = time();
        $_GET['status'] = 0;
        if (0 == $_GET['aid']) {
            M('seller_apply')->add($_GET);
        } else {
            M('seller_apply')->where(array('id' => $_GET['aid']))->save($_GET);
        }
        //print_r($_GET);
        $this->ajaxReturn(array('code' => '200', 'msg' => '成功'));
    }

    public function getOpenid()
    {
        $url = $_GET['url'];
        $url = urldecode($url);
        $result = httpRequest($url, 'GET');

        echo $result;
    }

    /**
     * 通过 mobile ，user_id 获取 验证码
     */
    public function getCode()
    {
        $mobile = $_GET['mobile'];
        $user_id = $_GET['user_id'];
        $code = M('sms_log')->where(array('mobile' => $mobile, 'session_id' => $user_id))->order('id DESC')->find();
        $result = array(
            'code' => $code['code'],
        );
        $this->ajaxReturn($result);
    }

    public function getUserid()
    {
        $open_id = $_GET['open_id'];
        if (!$open_id) {
            $this->ajaxReturn(array('code' => '400', 'msg' => 'open_id不能为空'));
            exit;
        }
        $result = M('users')->where(array('open_id' => $open_id))->field('user_id,mobile')->find();
        if (!$result) {
            $this->ajaxReturn(array('code' => '400', 'msg' => '用户不存在'));
            exit;
        }
        $result['code'] = 200;
        $this->ajaxReturn($result);
    }

    public function get_url_content($url)
    {
        $user_agent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)';
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            //'Content-Length: ' . strlen($data_string)
        ));
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        //var_dump($result);
        curl_close($ch);

        return $result;
    }

    public function api_notice_increment($url, $data)
    {
        $ch = curl_init();
        $header = 'Accept-Charset: utf-8';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        //     var_dump($tmpInfo);
        //    exit;
        if (curl_errno($ch)) {
            return false;
        } else {
            // var_dump($tmpInfo);
            return $tmpInfo;
        }
    }
}
