<?php
/**
 
 * ====
 
 * 
 * --------

 
 * ====
 * Author: IT宇宙人
 * Date: 2015-09-09
 */

namespace Home\Logic;

use Think\Model\RelationModel;
/**
 * 分类逻辑定义
 * Class CatsLogic
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
        $model = M('');
        $no_comment_goods_where['og.is_comment'] = 0;
        $no_comment_goods_where['og.order_id'] = $order_id;
        $no_comment_goods_where['og.deleted'] = 0;
        $no_comment_goods_list = $model
            ->table($db_prefix.'order_goods og')
            ->field('og.rec_id,og.order_id,og.goods_id,og.goods_name,og.spec_key_name,og.goods_price,g.original_img')
            ->join("LEFT JOIN __GOODS__ AS g ON g.goods_id = og.goods_id")
            ->where($no_comment_goods_where)
            ->select();
        return $no_comment_goods_list;
    }
}

 