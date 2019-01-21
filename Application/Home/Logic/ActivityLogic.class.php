<?php

namespace Home\Logic;

use Think\Model\RelationModel;
use Think\Db;

/**
 * 活动逻辑类.
 */
class ActivityLogic extends RelationModel
{
    /**
     * 优惠券列表.
     *
     * @param type $atype 排序类型 1:默认id排序，2:即将过期，3:面值最大
     * @param $user_id  用户ID
     * @param int $p 第几页
     *
     * @return array
     */
    public function getCouponList($atype, $user_id, $p = 1)
    {
        $time = time();
        $where = array('type' => 2, 'status' => 1, 'send_start_time' => ['elt', time()], 'send_end_time' => ['egt', time()]);
        $order = array('id' => 'desc');
        if (2 == $atype) {
            //即将过期
            $order = ['spacing_time' => 'asc'];
            $where["send_end_time-'$time'"] = ['egt', 0];
        } elseif (3 == $atype) {
            //面值最大
            $order = ['money' => 'desc'];
        }
        $coupon_list = M('coupon')->field("*,send_end_time-'$time' as spacing_time")
            ->where($where)->page($p, 15)->order($order)->select();
        if (is_array($coupon_list) && count($coupon_list) > 0) {
            if ($user_id) {
                $user_coupon = M('coupon_list')->where(['uid' => $user_id, 'type' => 2])->getField('cid', true);
            }
            if (!empty($user_coupon)) {
                foreach ($coupon_list as $k => $val) {
                    $coupon_list[$k]['isget'] = 0;
                    if (in_array($val['id'], $user_coupon)) {
                        $coupon_list[$k]['isget'] = 1;
                        unset($coupon_list[$k]);
                        continue;
                    }
                    $coupon_list[$k]['use_scope'] = C('COUPON_USER_TYPE')[$coupon_list[$k]['use_type']];
                }
            }
        }

        return $coupon_list;
    }

    /**
     * 获取优惠券查询对象
     *
     * @param int  $queryType   0:count 1:select
     * @param type $user_id
     * @param int  $type        查询类型 0:未使用，1:已使用，2:已过期
     * @param type $orderBy     排序类型，use_end_time、send_time,默认send_time
     * @param int  $order_money
     *
     * @return Query
     */
    public function getCouponQuery($queryType, $user_id, $type = 0, $orderBy = null, $order_money = 0)
    {
        $where['l.uid'] = $user_id;
        $where['c.status'] = 1;
        //查询条件
        if (empty($type)) {
            // 未使用
            $where['l.order_id'] = 0;
            $where['c.use_end_time'] = array('gt', time());
            $where['l.status'] = 0;
        } elseif (1 == $type) {
            //已使用
            $where['l.order_id'] = array('gt', 0);
            $where['l.use_time'] = array('gt', 0);
            $where['l.status'] = 1;
        } elseif (2 == $type) {
            //已过期
            $where['c.use_end_time'] = array('lt', time());
            $where['l.status|c.status'] = array('neq', 1);
        }

        if ('use_end_time' == $orderBy) {
            //即将过期，$type = 0 AND $orderBy = 'use_end_time'
            $order['c.use_end_time'] = 'asc';
        } elseif ('send_time' == $orderBy) {
            //最近到账，$type = 0 AND $orderBy = 'send_time'
            $where['l.send_time'] = array('lt', time());
            $order['l.send_time'] = 'desc';
        } elseif (empty($orderBy)) {
            $order = array('l.send_time' => 'DESC', 'l.use_time');
        }
        $condition = floatval($order_money) ? ' AND c.condition <= '.$order_money : '';
        $query = M('coupon_list')->alias('l')
            ->join('__COUPON__ c', 'l.cid = c.id'.$condition)
            ->where($where);

        if (0 != $queryType) {
            $query = $query->field('l.*,c.name,c.money,c.use_start_time,c.use_end_time,c.condition,c.use_type')
                    ->order($order);
        }

        return $query;
    }

    /**
     * 获取优惠券数目.
     *
     * @param $user_id
     * @param int  $type
     * @param null $orderBy
     * @param int  $order_money
     *
     * @return mixed
     */
    public function getUserCouponNum($user_id, $type = 0, $orderBy = null, $order_money = 0)
    {
        $query = $this->getCouponQuery(0, $user_id, $type, $orderBy, $order_money);

        return $query->count();
    }

    /**
     * 获取用户优惠券列表.
     *
     * @param $firstRow
     * @param $listRows
     * @param $user_id
     * @param int  $type
     * @param null $orderBy
     * @param int  $order_money
     *
     * @return mixed
     */
    public function getUserCouponList($firstRow, $listRows, $user_id, $type = 0, $orderBy = null, $order_money = 0)
    {
        $query = $this->getCouponQuery(1, $user_id, $type, $orderBy, $order_money);

        return $query->limit($firstRow, $listRows)->select();
    }

    /**
     * 领券中心.
     *
     * @param type $cat_id  领券类型id
     * @param type $user_id 用户id
     * @param type $p       第几页
     *
     * @return type
     */
    public function getCouponCenterList($cat_id, $user_id, $p = 1)
    {
        /* 获取优惠券列表 */
        $cur_time = time();
        $coupon_where = ['type' => 2, 'status' => 1, 'send_start_time' => ['elt', time()], 'send_end_time' => ['egt', time()]];
        $query = M('coupon')->alias('c')
            ->field('c.use_type,c.name,c.id,c.money,c.condition,c.createnum,c.send_num,c.send_end_time-'.$cur_time.' as spacing_time')
            ->where('((createnum-send_num>0 AND createnum>0) OR (createnum=0))')    //领完的也不要显示了
            ->where($coupon_where)->page($p, 15)
            ->order('spacing_time', 'asc');
        if ($cat_id > 0) {
            $query = $query->join('__GOODS_COUPON__ gc', 'gc.coupon_id=c.id AND gc.goods_category_id='.$cat_id);
        }
        $coupon_list = $query->select();

        if (!(is_array($coupon_list) && count($coupon_list) > 0)) {
            return [];
        }

        $user_coupon = [];
        if ($user_id) {
            $user_coupon = M('coupon_list')->where(['uid' => $user_id, 'type' => 2])->column('cid');
        }

        $types = [];
        if ($cat_id) {
            /* 优惠券类型格式转换 */
            $couponType = $this->getCouponTypes();
            foreach ($couponType as $v) {
                $types[$v['id']] = $v['mobile_name'];
            }
        }

        $store_logo = tpCache('shop_info.store_logo') ?: '';
        $Coupon = new Coupon();
        foreach ($coupon_list as $k => $coupon) {
            /* 是否已获取 */
            $coupon_list[$k]['use_type_title'] = $Coupon->getUseTypeTitleAttr(null, $coupon_list[$k]);
            $coupon_list[$k]['isget'] = 0;
            if (in_array($coupon['id'], $user_coupon)) {
                $coupon_list[$k]['isget'] = 1;
            }

            /* 构造封面和标题 */
            $coupon_list[$k]['image'] = $store_logo;
        }

        return  $coupon_list;
    }

    /**
     * 优惠券类型列表.
     *
     * @param type $p   第几页
     * @param type $num 每页多少，null表示全部
     *
     * @return type
     */
    public function getCouponTypes($p = 1, $num = null)
    {
        $list = M('coupon')->alias('c')
                ->join('__GOODS_COUPON__ gc', 'gc.coupon_id=c.id AND gc.goods_category_id!=0')
                ->where(['type' => 2, 'status' => 1])
                ->column('gc.goods_category_id');

        $result = M('goods_category')->field('id, mobile_name')->where('id', 'IN', $list)->page($p, $num)->select();
        $result = $result ?: [];
        array_unshift($result, ['id' => 0, 'mobile_name' => '精选']);

        return $result;
    }

    /**
     * 领券.
     *
     * @param $id 优惠券id
     * @param $user_id
     */
    public function get_coupon($id, $user_id)
    {
        if (empty($id)) {
            $return = ['status' => 0, 'msg' => '参数错误'];
        }
        if ($user_id) {
            $_SERVER['HTTP_REFERER'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Home/Activity/coupon_list');
            $coupon_info = M('coupon')->where(array('id' => $id, 'status' => 1))->find();
            if (empty($coupon_info)) {
                $return = ['status' => 0, 'msg' => '活动已结束或不存在，看下其他活动吧~', 'return_url' => $_SERVER['HTTP_REFERER']];
            } elseif ($coupon_info['send_end_time'] < time()) {
                //来晚了，过了领取时间
                $return = ['status' => 0, 'msg' => '抱歉，已经过了领取时间', 'return_url' => $_SERVER['HTTP_REFERER']];
            } elseif ($coupon_info['send_num'] >= $coupon_info['createnum'] && 0 != $coupon_info['createnum']) {
                //来晚了，优惠券被抢完了
                $return = ['status' => 0, 'msg' => '来晚了，优惠券被抢完了', 'return_url' => $_SERVER['HTTP_REFERER']];
            } else {
                if (M('coupon_list')->where(array('cid' => $id, 'uid' => $user_id))->find()) {
                    //已经领取过
                    $return = ['status' => 2, 'msg' => '您已领取过该优惠券', 'return_url' => $_SERVER['HTTP_REFERER']];
                } else {
                    $data = array('uid' => $user_id, 'cid' => $id, 'type' => 2, 'send_time' => time(), 'status' => 0);
                    M('coupon_list')->add($data);
                    M('coupon')->where(array('id' => $id, 'status' => 1))->setInc('send_num');
                    $return = ['status' => 1, 'msg' => '恭喜您，抢到'.$coupon_info['money'].'元优惠券!', 'return_url' => $_SERVER['HTTP_REFERER']];
                }
            }
        } else {
            $return = ['status' => 0, 'msg' => '请先登录', 'return_url' => U('User/login')];
        }

        return $return;
    }



    
 
}
