<?php
namespace Home\Logic;
use Think\Model\RelationModel;
use Think\Page;

/**
 *
 * Class orderLogic
 * @package Home\Logic
 */
class StoreLogic extends RelationModel
{
    /**
     * 更新店铺评分
     * @param $store_id
     */
    public function updateStoreScore($store_id){
        $store_where = array('store_id'=>$store_id,'deleted'=>0);
        $store['store_desccredit'] = M('order_comment')->where($store_where)->avg('describe_score');
        $store['store_servicecredit'] = M('order_comment')->where($store_where)->avg('seller_score');
        $store['store_deliverycredit'] = M('order_comment')->where($store_where)->avg('logistics_score');
        M('store')->where(array('store_id'=>$store_id))->save($store);
    }
    /**
     * 获取行业评分
     * @param $sc_id
     * @return array
     */
    public function storeComparison($sc_id)
    {
        $comparison_where = array('sc_id' => $sc_id, 'deleted' => 0);
        $comparison['store_desccredit_avg'] =  number_format(M('store')->where($comparison_where)->avg('store_desccredit'),1);
        $comparison['store_servicecredit_avg'] = number_format(M('store')->where($comparison_where)->avg('store_servicecredit'),1);
        $comparison['store_deliverycredit_avg'] = number_format( M('store')->where($comparison_where)->avg('store_deliverycredit'),1);
        return $comparison;
    }
    /**
     * 获取店铺评分
     * @param $store_id
     * @return array
     */
    public function storeCommentStatistics($store_id)
    {
        $store = M('store')->where(array('store_id' => $store_id, 'deleted' => 0))->find();
        $store_comment_score = array(
            'store_desccredit' => number_format($store['store_desccredit'], 1),
            'store_servicecredit' => number_format($store['store_servicecredit'], 1),
            'store_deliverycredit' => number_format($store['store_deliverycredit'], 1)
        );
        return $store_comment_score;
    }

    /**
     * 获取百分数
     * @param $comparison
     * @param $store_comment_score
     * @return array
     */
    public function storeMatch($comparison, $store_comment_score)
    {
        if ($store_comment_score['store_desccredit'] == 0
            || $store_comment_score['store_servicecredit'] == 0
            || $store_comment_score['store_deliverycredit'] == 0
        ) {
            $store_match = array(
                'desccredit_match' => 0,
                'servicecredit_match' => 0,
                'servicecredit_deliverycredit' => 0
            );
        } else {
            $store_match = array(
                'desccredit_match' => $this->getPercent($store_comment_score['store_desccredit'], $comparison['store_desccredit_avg']),
                'servicecredit_match' => $this->getPercent($store_comment_score['store_servicecredit'], $comparison['store_servicecredit_avg']),
                'deliverycredit_match' => $this->getPercent($store_comment_score['store_deliverycredit'], $comparison['store_deliverycredit_avg'])
            );
        }
        return $store_match;
    }


    public function getPercent($score, $avg)
    {
        if ($avg == 0) {
            return 100;
        } else {
            return round(($score - $avg) / $avg * 100, '2');
        }
    }


    /**
     * 获取用户收藏的店铺
     * @author dyr
     * @param $user_id
     * @param null $sc_id
     * @return mixed
     */
    public function getCollectStore($user_id,$sc_id=null)
    {
        if(!empty($sc_id)){
            $store_collect_where['s.sc_id'] = $sc_id;
        }
        $store_collect_where['sc.user_id'] = $user_id;
        $db_prefix = C('DB_PREFIX');
        $model = M('');
        $count = $model
                ->table($db_prefix.'store_collect sc')
                ->join('LEFT JOIN '.$db_prefix.'store AS s ON s.store_id = sc.store_id')
                ->where($store_collect_where)
                ->count();
        $page = new Page($count,10);
        $show = $page->show();
        if ($count === 0){
            $return['result'] = array();
            $return['show'] = $show;
            return $return;
        }
        $store_collect_list = $model
            ->field('sc.log_id,s.store_id,s.store_qq,s.store_name,s.store_logo,s.store_avatar,s.store_qq,s.store_desccredit,s.store_servicecredit,
            s.store_deliverycredit,r1.name as province_name,r2.name as city_name,r3.name as district_name,s.deleted as goods_array')
            ->table($db_prefix.'store_collect sc')
            ->join('INNER JOIN '.$db_prefix.'store AS s ON s.store_id = sc.store_id')
            ->join('LEFT JOIN '.$db_prefix . 'region As r1 ON r1.id = s.province_id')
            ->join('LEFT JOIN '.$db_prefix . 'region As r2 ON r2.id = s.city_id')
            ->join('LEFT JOIN '.$db_prefix . 'region As r3 ON r3.id = s.district')
            ->where($store_collect_where)
            ->order('sc.add_time DESC')
            ->limit($page->firstRow,$page->listRows)
            ->select();
        foreach($store_collect_list as $key=>$value){
            $store_collect_list[$key]['goods_array'] = D("store")->getStoreGoods($value['store_id'],3);
        }
        $return['result'] = $store_collect_list;
        $return['show'] = $show;
        return $return;
    }

    /**
     * 店铺街
     * @param null $sc_id 分类id
     * @param int $item 记录条数
     */
    public function getStoreList($sc_id = null, $item = 10)
    {
        $db_prefix = C('DB_PREFIX');
        $store_where = array('s.store_state' => 1);
        $model = M('');
        if (!empty($sc_id)) {
            $store_where['s.sc_id'] = $sc_id;
        }
        $store_count = $model->table($db_prefix . 'store s')->where($store_where)->count();
        $page = new Page($store_count, $item);
        $show = $page->show();
        $store_list = $model
            ->field("s.store_id,s.store_qq,s.store_name,s.store_logo,s.store_banner,s.store_aliwangwang,s.store_qq,s.store_desccredit,s.store_servicecredit,
            s.store_deliverycredit,r1.name as province_name,r2.name as city_name,r3.name as district_name,s.deleted as goods_array")
            ->table($db_prefix . 'store s')
            ->join('LEFT JOIN '.$db_prefix . 'region As r1 ON r1.id = s.province_id')
            ->join('LEFT JOIN '.$db_prefix . 'region As r2 ON r2.id = s.city_id')
            ->join('LEFT JOIN '.$db_prefix . 'region As r3 ON r3.id = s.district')
            ->where($store_where)
            ->order('s.store_sort DESC')
            ->limit($page->firstRow, $page->listRows)
            ->select();
        foreach ($store_list as $key => $value) {
            $store_list[$key]['goods_array'] = D("store")->getStoreGoods($value['store_id'], 4);
        }
        $return['result'] = $store_list;
        $return['show'] = $show;
        return $return;
    }


    /**
     * 新订单
     * 发送短信给商家的逻辑
     * 
     */
    public function seng_msg_to_store($order)
    {

        $store_id = $order['store_id'];
        $store = M('store')->where(array('store_id'=>$store_id))->find(); 
        $mobile = $store['store_phone'];

        if(!$mobile || strlen($mobile) !== 11 ){
            return false;
        }

        $templateCode = "SMS_128635208";
        
        $templateParam = json_encode(array(  // 短信模板中字段的值
            "username"=> $store['store_name'],
            "consignee"=>"（你的店铺有新订单）"
        ), JSON_UNESCAPED_UNICODE);
            
        $SmsLogic = new \Home\Logic\SmsLogic();
        $resp = $SmsLogic->sendSms($mobile,$templateCode,$templateParam);
    
        if ($resp && $resp->Code == "OK")  
        {
            
            return "OK";
        }else{
            return $resp->Message;
        }
       
    }

    /**
     * 发货
     * 通知客户
     */
    public function send_msg_delivery($order_id){
       
        $order = M('order')->where(array('order_id'=> $order_id ))->find();
       
        $mobile = $order['mobile'];
        if(!$mobile || strlen($mobile) !== 11 ){
            return false;
        }

        $templateCode = "SMS_128635208";
        
        $templateParam = json_encode(array(  // 短信模板中字段的值
            "username" => $order['consignee'],
            "consignee" => $order['consignee'].$mobile
        ), JSON_UNESCAPED_UNICODE);
            
        $SmsLogic = new \Home\Logic\SmsLogic();
        $resp = $SmsLogic->sendSms($mobile,$templateCode,$templateParam);
    
        if ($resp && $resp->Code == "OK")  
        {
            
            return "OK";
        }else{
            return $resp->Message;
        }
    }


}