<?php
namespace Home\Logic;
use Think\Model\RelationModel;

/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */

class DistributLogic extends RelationModel
{
//     public function rebate_log($order)
//     {      
//         file_put_contents("file2.txt", "1111");      
//         // 如果这笔订单没有分销金额	 
//         $user = M('users')->where("user_id", $order['user_id'])->find();
      
//         $pattern = tpCache('distribut.pattern'); // 分销模式  
//         $first_rate = tpCache('distribut.first_rate'); // 一级比例
//         $second_rate = tpCache('distribut.second_rate'); // 二级比例  
//         $third_rate = tpCache('distribut.third_rate'); // 三级比例  
       
//         //按照商品分成 每件商品的佣金拿出来
//         if($pattern  == 0) 
//         {
//            // 获取所有商品分类 
//             $cat_list =  M('goods_category')->getField('id,parent_id,commission_rate');             
//             $order_goods = M('order_goods')->where("order_id", $order['order_id'])->select(); // 订单所有商品
//             $commission = 0;
//             foreach($order_goods as $k => $v)
//             {
//                    $tmp_commission = 0;
//                    $goods = M('goods')->where("goods_id", $v['goods_id'])->find(); // 单个商品的佣金
//                    $tmp_commission = $goods['commission'];
//                    // 如果商品没有设置分佣,则找他所属分类看是否设置分佣
//                    if($tmp_commission == 0)
//                    {
//                       $commission_rate = $cat_list[$goods['cat_id']]['commission_rate']; // 查看分类平台抽成比例
                      
//                       if($commission_rate == 0) // 如果它没有设置分类则找他老爸分类看看是否设置分佣
//                       {
//                           // 找出他老爸
//                           $parent_id = $cat_list[$goods['cat_id']]['parent_id'];
//                           $commission_rate = $cat_list[$parent_id]['commission_rate']; // 查看 老爸分类平台抽成比例
//                       } 
//                       if($commission_rate == 0) // 如果它老爸没有设置分类则找他爷爷分类看看是否设置分佣
//                       {
//                           // 找出他爷爷
//                           $grandfather_id = $cat_list[$parent_id]['parent_id'];
//                           $commission_rate = $cat_list[$grandfather_id]['commission_rate']; // 查看爷爷分类平台抽成比例
//                       } 
                      
//                       $tmp_commission = $v['member_goods_price'] * ($commission_rate / 100); // 这个商品的分佣 =  商品价 诚意商品分类设置的平台抽成比例
//                     }
                                       
//                    $tmp_commission = $tmp_commission  * $v['goods_num']; // 单个商品的分佣乘以购买数量                    
//                    $commission += $tmp_commission; // 所有商品的累积佣金
//             }                        
//         }else{
//             $order_rate = tpCache('distribut.order_rate'); // 订单分成比例  
//             $commission = $order['goods_price'] * ($order_rate / 100); // 订单的商品总额 乘以 订单分成比例
//         }
//            file_put_contents("file1.txt", $commission);      
//         // 如果这笔订单没有分销金额
//         if($commission == 0)
//             return false;
// file_put_contents("file222.txt", $first_rate);     
//            $first_money = $commission * ($first_rate / 100); // 一级赚到的钱
//            $second_money = $commission * ($second_rate / 100); // 二级赚到的钱
//            $thirdmoney = $commission * ($third_rate / 100); // 三级赚到的钱
        
//            //  微信消息推送
//            $wx_user = M('wx_user')->find();
           
//            $jssdk = new \Mobile\Logic\Jssdk($wx_user['appid'],$wx_user['appsecret']);
//               file_put_contents("file111.txt", $user['first_leader']);   
//          // 一级 分销商赚 的钱. 小于一分钱的 不存储
//         if($user['first_leader'] > 0 && $first_money > 0.01)
//         {
//             file_put_contents("file111.txt", $user['first_leader']);     
//            $data = array(             
//                'user_id' =>$user['first_leader'],
//                'buy_user_id'=>$user['user_id'],
//                'nickname'=>$user['nickname'],
//                'order_sn' => $order['order_sn'],
//                'order_id' => $order['order_id'],
//                'goods_price' => $order['goods_price'],
//                'money' => $first_money,
//                'level' => 1,
//                'create_time' => time(),             
//            );                  
//            M('rebate_log')->add($data);
//            // // 微信推送消息
//            $tmp_user = M('users')->where("user_id", $user['first_leader'])->find();
//            if($tmp_user['oauth']== 'weixin')
//            {
//                $wx_content = "你的一级下线,刚刚下了一笔订单:{$order['order_sn']} 如果交易成功你将获得 ￥".$first_money."奖励 !";
//                $jssdk->push_msg($tmp_user['openid'],$wx_content);
//            }                       
//         }
//          // 二级 分销商赚 的钱.
//         if($user['second_leader'] > 0 && $second_money > 0.01)
//         {         
//            $data = array(
//                'user_id' =>$user['second_leader'],
//                'buy_user_id'=>$user['user_id'],
//                'nickname'=>$user['nickname'],
//                'order_sn' => $order['order_sn'],
//                'order_id' => $order['order_id'],
//                'goods_price' => $order['goods_price'],
//                'money' => $second_money,
//                'level' => 2,
//                'create_time' => time(),             
//            );                  
//            M('rebate_log')->add($data);         
//            // // 微信推送消息
//            // $tmp_user = M('users')->where("user_id", $user['second_leader'])->find();
//            if($tmp_user['oauth']== 'weixin')
//            {
//                $wx_content = "你的二级下线,刚刚下了一笔订单:{$order['order_sn']} 如果交易成功你将获得 ￥".$second_money."奖励 !";
//                $jssdk->push_msg($tmp_user['openid'],$wx_content);
//            }              
//         }
//          // 三级 分销商赚 的钱.
//         if($user['third_leader'] > 0 && $thirdmoney > 0.01)
//         {                  
//            $data = array(
//                'user_id' =>$user['third_leader'],
//                'buy_user_id'=>$user['user_id'],
//                'nickname'=>$user['nickname'],
//                'order_sn' => $order['order_sn'],
//                'order_id' => $order['order_id'],
//                'goods_price' => $order['goods_price'],
//                'money' =>$thirdmoney,
//                'level' => 3,
//                'create_time' => time(),             
//            );                  
//            M('rebate_log')->add($data);      
//            // // 微信推送消息
//            $tmp_user = M('users')->where("user_id", $user['third_leader'])->find();
//            if($tmp_user['oauth']== 'weixin')
//            {
//                $wx_content = "你的三级下线,刚刚下了一笔订单:{$order['order_sn']} 如果交易成功你将获得 ￥".$thirdmoney."奖励 !";
//                $jssdk->push_msg($tmp_user['openid'],$wx_content);
//            }              
           
//         }
//         M('order')->where("order_id", $order['order_id'])->save(array("is_distribut"=>1));  //修改订单为已经分成
//     }




    /**
     * 分销记录逻辑
     */
    public function rebate_log($order)
    {
    
        $order_id = $order['order_id'];
        if(!$order_id){
            return "order_id为空";
            exit();
        }

        //找出购买者的 first_leader，second_leader，third_leader
        $user_id = $order['user_id'];
        $data = M('users')->where(array('user_id'=>$user_id))->field('first_leader,second_leader,third_leader')->find();

        M('order')->where(array('order_id'=>$order_id))->save($data);

        $ex =  M('distribut')->where(array('order_id'=>$order_id))->find();
       
        if(!$ex){
            $data['order_id'] = $order_id;
            M('distribut')->add($data);
        }
       
        //写入rebate_log
        if((int)$data['first_leader'] > 0){
            $res['first_leader'] = $this->save_rebate_log($order_id,$data['first_leader'],1);
        }else{
            $res['first_leader'] = "无first_leader";
        }

        if((int)$data['second_leader'] > 0){
            $res['second_leader'] = $this->save_rebate_log($order_id,$data['second_leader'],2);
        }else{
            $res['second_leader'] = "无second_leader";
        }

        if((int)$data['third_leader'] > 0){
            $res['third_leader'] = $this->save_rebate_log($order_id,$data['third_leader'],3);
        }else{
            $res['third_leader'] = "无third_leader";
        }

    
        return json_encode($res);
    }

  
    /**
     * 写入
     */
     public function save_rebate_log($order_id,$user_id,$grade){
        header("Content-type:text/html;charset=utf-8");
       
        // user_id  为  获佣金 的ID
        //判断是否存在
        $cz = M('rebate_log')->where(array('user_id'=>$user_id,'order_id'=>$order_id))->find();
        if(!$cz){
            //不存在
            $order = M('order')->where(array('order_id'=>$order_id))->find();
            $data['buy_user_id'] = $order['user_id'];
            $data['user_id'] = $user_id;
            $data['nickname'] = $order['consignee'];
            $data['order_sn'] = $order['order_sn'];
            $data['order_id'] = $order_id;
            $data['goods_price'] = $order['goods_price'];

            //$grade   1   2    3
            $store_id = $order['store_id'];

            $store_disttribut = M('store_distribut')->where(array('store_id'=>$store_id))->find();
          

            if(!$store_disttribut ){
                return "该店无分销";
            }
            //该店没分销

            //该店关闭分销
            if($store_disttribut['switch'] == 0 ){
                return "该店关闭分销功能";
            }

            $data['money'] =  $this->get_money_by_order_id($order_id,$grade,$store_disttribut);
          
            //获佣金额为空，就不要插入了
            if((float)$data['money'] == 0){
                return "获佣金额为空";
            }

            //获佣用户级别
            $data['level'] = M('users')->where(array('user_id'=>$data['user_id']))->getField('level');
            $data['create_time'] = time();
            $data['status'] = 0;
            $data['store_id'] = $store_id;

            M('rebate_log')->add($data);
            return "写入成功";
            
        }
     }


     /**
      * 通过 order_id +  grade  获取 分佣金额
      */
      private function get_money_by_order_id($order_id,$grade, $store_disttribut){
        $order_ids = M('order_goods')->where(array('order_id'=>$order_id))->select();
        foreach($order_ids as $v){
            $goods_id = $v['goods_id'];
            $distribut = M('goods')->where(array('goods_id'=>$goods_id))->getField('distribut');
                switch ($grade)
                {
                    case 1:
                        $data['money'] = $data['money'] + ( $store_disttribut['first_rate'] / 100 ) * $distribut;
                        break;
                    case 2:
                        $data['money'] =  $data['money'] + ( $store_disttribut['second_rate'] / 100 ) * $distribut;
                        break;
                    case 3:
                        $data['money'] =  $data['money'] + ( $store_disttribut['third_rate'] / 100 ) * $distribut;
                        break;
                
                    default:
                        $data['money'] =  0;
                }
        }
        return $data['money'];
        
    }

    /**
      * 自动分成 符合条件的 分成记录
      */
      function auto_confirm(){
         
        $switch = tpCache('distribut.switch');
       
        if($switch == 0)
            return false;
       
        $today_time = time();
       
        $distribut_date = tpCache('distribut.date');

        $distribut_time = $distribut_date * (60 * 60 * 24); // 计算天数 时间戳
        $rebate_log_arr = M('rebate_log')->where("status = 2 and ($today_time - confirm) >  $distribut_time")->select();

        foreach ($rebate_log_arr as $key => $val)
        {
            accountLog($val['user_id'], $val['money'], 0,"订单:{$val['order_sn']}分佣",$val['money']);             
            $val['status'] = 3;
            $val['confirm_time'] = $today_time;
            $val['remark'] = $val['remark']."满{$distribut_date}天,程序自动分成.";
            M("rebate_log")->where(array("id"=>$val['id']))->save($val);
        }
    }


}