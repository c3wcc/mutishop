<?php

namespace WXAPI\Controller;

use Think\Page;

class ActivityController extends BaseController
{
    public function index()
    {
        $this->display();
    }

    /**
     * 商品详情页.
     */
    public function group()
    {
        $goods_id = $_REQUEST['id'];
        $group_buy_info = M('GroupBuy')->where("goods_id = $goods_id and ".time().' >= start_time and '.time().' <= end_time ')->find(); // 找出这个商品

        $group_buy_info['end_time'] = date('Y-m-d', $group_buy_info['end_time']);

        //$http = SITE_URL; // 网站域名
        $goods_id = $_REQUEST['id'];
        $where['goods_id'] = $goods_id;
        $model = M('Goods');

        $goods = $model->where($where)->find();

        // 处理商品属性
            $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
            $goods_attr_list = M('GoodsAttr')->where("goods_id = $goods_id")->select(); // 查询商品属性表
            foreach ($goods_attr_list as $key => $val) {
                $goods_attr_list[$key]['attr_name'] = $goods_attribute[$val['attr_id']];
            }
        $goods['goods_attr_list'] = $goods_attr_list ? $goods_attr_list : '';

        // 处理商品规格
        $Model = new \Think\Model();
        // 商品规格 价钱 库存表 找出 所有 规格项id
        $keys = M('SpecGoodsPrice')->where("goods_id = $goods_id")->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
        if ($keys) {
            $specImage = M('SpecImage')->where("goods_id = $goods_id and src != '' ")->getField('spec_image_id,src'); // 规格对应的 图片表， 例如颜色
            $keys = str_replace('_', ',', $keys);
            $sql = "SELECT a.name,a.order,b.* FROM __PREFIX__spec AS a INNER JOIN __PREFIX__spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY a.order";
            $filter_spec2 = $Model->query($sql);
            foreach ($filter_spec2 as $key => $val) {
                $filter_spec[] = array(
                            'spec_name' => $val['name'],
                            'item_id' => $val['id'],
                            'item' => $val['item'],
                            'src' => $specImage[$val['id']] ? SITE_URL.$specImage[$val['id']] : '',
                    );
            }
            $goods['goods_spec_list'] = $filter_spec;
        }

        // print_r($filter_spec);
        //print_r($goods_attr_list);
        //print_r($filter_spec);

        $goods['goods_content'] = str_replace('/Public/upload/', SITE_URL.'/Public/upload/', $goods['goods_content']);
        $goods['goods_content'] = htmlspecialchars_decode($goods['goods_content']);
        $goods['original_img'] = SITE_URL.$goods['original_img'];

        $specs = $goods['goods_spec_list'];
        //print_r($specs);
        $pos = 0;
        $keys = array();
        foreach ($specs as $key => $value) {
            //$spec_arrays[$value['spec_name']][] = $value;
            if (!array_key_exists($value['spec_name'], $keys)) {
                $keys[$value['spec_name']] = $pos++;
            }
        }
        //print_r($keys);

        foreach ($specs as $key => $value) {
            $value['isClick'] = 0;
            $spec_arrays[$keys[$value['spec_name']]][] = $value;
        }

        foreach ($spec_arrays as $key => $value) {
            $spec_arrays[$key][0]['isClick'] = 1;
        }

        $goods['goods_spec_list'] = $spec_arrays;

        $return['goods'] = $goods;
        $return['spec_goods_price'] = M('spec_goods_price')->where("goods_id = $goods_id")->getField('key,price,store_count'); // 规格 对应 价格 库存表
        $return['gallery'] = M('goods_images')->field('image_url')->where(array('goods_id' => $goods_id))->select();
        foreach ($return['gallery'] as $key => $val) {
            $return['gallery'][$key]['image_url'] = SITE_URL.$return['gallery'][$key]['image_url'];
        }
        //获取最近的两条评论
        $latest_comment = M('comment')->where("goods_id={$goods_id} AND is_show=1 AND parent_id=0")->limit(2)->select();

        foreach ($latest_comment as $key => $value) {
            $latest_comment[$key]['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
        }

        $return['comment'] = $latest_comment ? $latest_comment : '';

        if (!$goods) {
            $json_arr = array('status' => -1, 'msg' => '没有该商品', 'result' => '');
        } else {
            $json_arr = array('status' => 1, 'msg' => '获取成功', 'result' => $return, 'groupInfo' => $group_buy_info);
        }

        $json_str = json_encode($json_arr);
        exit($json_str);
    }

    /**
     * 团购活动列表.
     */
    public function group_list()
    {
        $count = M('GroupBuy')->where(time().' >= start_time and '.time().' <= end_time ')->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 20); // 实例化分页类 传入总记录数和每页显示的记录数

        $list = M('GroupBuy')->where(time().' >= start_time and '.time().' <= end_time ')->limit($Page->firstRow.','.$Page->listRows)->select(); // 找出这个商品

        foreach ($list as $key => $value) {
            $list[$key]['image'] = 'http://'.$_SERVER['SERVER_NAME'].M('goods')->where(array('goods_id' => $value['goods_id']))->getField('original_img');
            $list[$key]['end_time'] = date('Y-m-d H-i-s', $value['end_time']);
        }

        $json_arr = array('status' => 1, 'msg' => '获取成功', 'result' => $list);
        $json_str = json_encode($json_arr);
        exit($json_str);
    }
}
