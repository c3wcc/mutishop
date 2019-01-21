<?php

/**
 
 * ====
 
 * 
 * --------

 
 * ====
 * Author: 当燃
 * Date: 2016-06-09
 */
 

namespace Seller\Logic;

use Think\Model;

class StoreLogic extends Model
{    
    
    /**
     * 获取指定店铺信息
     * @param $uid int 用户UID
     * @param bool $relation 是否关联查询
     *
     * @return mixed 找到返回数组
     */
    public function detail($store_id, $relation = true)
    {
        $user = D('Store')->where(array('store_id' => $store_id))->relation($relation)->find();
        return $user;
    }
  
}