<?php
/**
 
 * ====
 
 * 
 * --------

 
 * ====
 * Author: IT宇宙人
 * Date: 2015-09-09
 */

namespace Mobile\Logic;

use Think\Model\RelationModel;

/**
 * Class OrderGoodsLogic
 * @package Home\Logic
 */
class OrderGoodsLogic extends RelationModel
{
    /**
     * 查找订单下的所有未评价的商品
     * @param $order_id
     * @return mixed
     */
    public function get_no_comment_goods_list($order_id){
        $db_prefix = C('DB_PREFIX');
        $no_comment_goods_where['og.is_comment'] = 0;
        $no_comment_goods_where['og.order_id'] = $order_id;
        $no_comment_goods_where['og.deleted'] = 0;
        $no_comment_goods_list = M('')
            ->table($db_prefix.'order_goods og')
            ->field('og.rec_id,og.order_id,og.goods_id,og.goods_name,og.spec_key_name,og.goods_price,g.original_img')
            ->join("LEFT JOIN __GOODS__ as g ON g.goods_id = og.goods_id")
            ->where($no_comment_goods_where)
            ->select();
        return $no_comment_goods_list;
    }

    /**
     * 获取订单里没有被评价的商品（单条）
     * @param $order_id
     * @param $goods_id
     * @return mixed
     */
    public function get_no_comment_goods($order_id,$goods_id){
        $db_prefix = C('DB_PREFIX');
        $no_comment_goods_where['og.is_comment'] = 0;
        $no_comment_goods_where['og.order_id'] = $order_id;
        $no_comment_goods_where['og.deleted'] = 0;
        $no_comment_goods_where['og.goods_id'] = $goods_id;
        $no_comment_goods_list = M('')
            ->table($db_prefix.'order_goods og')
            ->field('og.rec_id,og.order_id,og.goods_id,og.goods_name,og.spec_key_name,og.goods_price,g.original_img')
            ->join("LEFT JOIN __GOODS__ as g ON g.goods_id = og.goods_id")
            ->where($no_comment_goods_where)
            ->find();
        return $no_comment_goods_list;
    }

}

 