<?php
/**
 
 * ====
 
 * 
 * --------

 
 * ====
 * Author: IT宇宙人
 * Date: 2015-09-09
 */

namespace Seller\Model;
use Think\Model;
class ShippingAreaModel extends Model {

    /**
     * 获取店铺的配送区域
     * @param $store_id
     * @return mixed
     */
    public function getShippingArea($store_id)
    {
        $shipping_areas = M('shipping_area')->where(array('store_id'=>$store_id))->select();
        foreach($shipping_areas as $key => $val){
            $shipping_areas[$key]['config'] = unserialize($shipping_areas[$key]['config']);
        }
        return $shipping_areas;
    }

}
