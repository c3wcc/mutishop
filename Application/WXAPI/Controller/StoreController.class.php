<?php

namespace WXAPI\Controller;

use WXAPI\Logic\GoodsLogic;
use WXAPI\Logic\StoreLogic;
use Think\Page;

class StoreController extends BaseController
{
    public function _initialize()
    {
        $store_id = I('store_id', 1);
        $this->store = M('store')->where(array('store_id' => $store_id))->find();
    }

    public function getStoreClass()
    {
        $store_class = M('store_class')->order('sc_sort asc')->select();
        exit(json_encode($store_class));
    }

    public function getStores()
    {
        $store_class = M('store')->where(array('sc_id' => $_GET['cid']))->select();
        foreach ($store_class as $k => $v) {
            $store_class[$k]['store_logo'] = SITE_URL.$v['store_logo'];
            $store_class[$k]['goods_num'] = M('goods')->where(array('store_id' => $v['store_id']))->count();
            $store_class[$k]['goods'] = M('goods')->where(array('store_id' => $v['store_id']))->limit('0 , 4')->select();
            foreach ($store_class[$k]['goods'] as $key => $value) {
                $store_class[$k]['goods'][$key]['original_img'] = SITE_URL.$value['original_img'];
            }
        }
        exit(json_encode($store_class));
    }

    /**
     * 关于店铺(店铺基本信息).
     */
    public function about()
    {
        $store_id = I('store_id', 1); // 当前分类id //  "store_id , store_name , grade_id , province_id , city_id , store_address , store_time"
        $store = M('store')->where("store_id=$store_id")->find();

        $province_id = $store['province_id'];
        $city_id = $store['city_id'];

        //所在地
        $regions = M('region')->where(' id in( '.$store['province_id'].' , '.$store['city_id'].' , '.$store['district'].' )')->select();
        $region = '';
        foreach ($regions as $k => $v) {
            $region .= $v['name'];
        }
        $store['location'] = $region;

        $gradgeId = $store['grade_id'];

        //查询店铺等级
        $gradgeName = M('store_grade')->where("sg_id = $gradgeId")->getField('sg_name');
        $store['grade_name'] = $gradgeName;

        $json_arr = array('status' => 1, 'msg' => '获取成功', 'result' => $store);

        exit(json_encode($json_arr));
    }

    /***
     * 店铺
     */
    public function index()
    {
        $store_id = I('store_id', 1);
        $store = M('store')->where("store_id=$store_id")->find();

        //新品
        $new_goods = M('goods')->field('goods_content', true)->where(array('store_id' => $store_id, 'is_new' => 1))->order('goods_id desc')->limit(10)->select();
        //推荐商品
        $recomend_goods = M('goods')->field('goods_content', true)->where(array('store_id' => $store_id, 'is_recommend' => 1))->order('goods_id desc')->limit(10)->select();
        //热卖商品
        $hot_goods = M('goods')->field('goods_content', true)->where(array('store_id' => $store_id, 'is_hot' => 1))->order('goods_id desc')->limit(10)->select();

        //店铺商品总数
        $storeCount = M('goods')->where('store_id='.$store_id)->sum('store_count');

        $store['new_goods'] = $new_goods;
        $store['recomend_goods'] = $recomend_goods;
        $store['hot_goods'] = $hot_goods;
        $store['store_count'] = $storeCount;

        $json_arr = array('status' => 1, 'msg' => '获取成功', 'result' => $store);

        exit(json_encode($json_arr));
    }

    /**
     * 搜索店铺内的商品
     */
    public function searchStoreGoodsClass()
    {
        $store_id = I('store_id', 1);

        $search_key = I('search_key');  // 关键词搜索

        $where = ' where 1 = 1 ';
        $orderby = I('orderby', 'goods_id'); // 排序
        $orderdesc = I('orderdesc', 'desc'); // 升序 降序

        $search_key && $where .= " and (goods_name like '%$search_key%' or keywords like '%$search_key%')";

        if ($store_id > 0) {
            $where .= ' and store_id = '.$store_id;     //店铺ID
        }

        $cat_id = I('cat_id', 0); // 所选择的商品分类id
        if ($cat_id > 0) {
            $where .= ' and store_cat_id2 = '.$cat_id; // 初始化搜索条件
        }

        $Model = new \Think\Model();
        $limit = ' limit 1';

        $list = M('goods')->where('store_id = 1')->field('goods_remark,goods_content', true)->limit(0, 10)->select(); // ->query("select *  from __PREFIX__goods $where $limit ");

        /*
        $result = $Model->query("select count(1) as count from __PREFIX__goods $where ");

        $count = $result[0]['count'];

        $_GET['p'] = $_REQUEST['p'];

        $page = new Page($count,10);

        $order = " order by $orderby $orderdesc "; // 排序
        $limit = " limit ".$page->firstRow.','.$page->listRows;
        $list = $Model->query("select *  from __PREFIX__goods $where $order $limit"); */

        $json_arr = array('status' => 1, 'msg' => '获取成功', 'result' => $list);
        $json_str = json_encode($json_arr);

        exit(json_encode($json_arr));
    }

    /**
     * 获取店铺商品分类.
     */
    public function storeGoodsClass()
    {
        $store_id = $this->store['store_id'];
        $goods_logic = new GoodsLogic();
        $store_goods_class = $goods_logic->getStoreGoodsClass($store_id);
        $json_arr = array('status' => 1, 'msg' => '获取成功', 'result' => $store_goods_class);
        exit(json_encode($json_arr));
    }

    /**
     * @author dyr
     * 修改于2016/08/26
     * 获取店铺商品列表
     */
    public function storeGoods()
    {
        C('URL_MODEL', 0); // 返回给手机app 生成路径格式 为 普通 index.php?=api&c=  最普通的路径格式

        $store_id = $_GET['store_id'];
        $key = I('get.key', '');

        $p = I('get.p', 0); // 当前分类id
        $filter_param = array(); // 帅选数组

        $brand_id = I('get.brand_id', 0);
        $spec = I('get.spec', 0); // 规格
        $attr = I('get.attr', ''); // 属性
        $sort = I('get.sort', 'goods_id'); // 排序
        $sort_asc = I('get.sort_asc', 'asc'); // 排序
        $price = I('get.price', ''); // 价钱
        $start_price = trim(I('post.start_price', '0')); // 输入框价钱
        $end_price = trim(I('post.end_price', '0')); // 输入框价钱
        if ($start_price && $end_price) {
            $price = $start_price.'-'.$end_price;
        } // 如果输入框有价钱 则使用输入框的价钱

        $brand_id && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
        $spec && ($filter_param['spec'] = $spec); //加入帅选条件中
        $attr && ($filter_param['attr'] = $attr); //加入帅选条件中
        $price && ($filter_param['price'] = $price); //加入帅选条件中

        $goodsLogic = new \Home\Logic\GoodsLogic(); // 前台商品操作逻辑类

        if ('' == $key) {
            $filter_goods_id = M('goods')->where(array('is_on_sale' => 1, 'store_id' => $store_id))->cache(true)->getField('goods_id', true);
        } else {
            $filter_goods_id = M('goods')->where(array('is_on_sale' => 1, 'store_id' => $store_id, 'goods_name' => array('like', "%$key%")))->cache(true)->getField('goods_id', true);
        }
        //print_r($filter_goods_id);
        // 过滤帅选的结果集里面找商品
        if ($brand_id || $price) {// 品牌或者价格
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id, $price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_1); // 获取多个帅选条件的结果 的交集
        }
        if ($spec) {// 规格
            $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_2); // 获取多个帅选条件的结果 的交集
        }
        if ($attr) {// 属性
            $goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_3); // 获取多个帅选条件的结果 的交集
        }

        $filter_menu = $goodsLogic->get_filter_menu($filter_param, 'goodsList'); // 获取显示的帅选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id, $filter_param, 'goodsList'); // 帅选的价格期间
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id, $filter_param, 'goodsList', 1); // 获取指定分类下的帅选品牌
        $filter_spec = $goodsLogic->get_filter_spec($filter_goods_id, $filter_param, 'goodsList', 1); // 获取指定分类下的帅选规格
        $filter_attr = $goodsLogic->get_filter_attr($filter_goods_id, $filter_param, 'goodsList', 1); // 获取指定分类下的帅选属性

        $count = count($filter_goods_id);
        $page = new Page($count, 5);
        $page->firstRow = $p * $page->listRows;
        if ($count > 0) {
            $goods_list = M('goods')->field('goods_id,cat_id2,goods_sn,goods_name,shop_price,comment_count,original_img,sales_sum')->where('goods_id in ('.implode(',', $filter_goods_id).')')->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();

            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
        }
        $goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组

        foreach ($goods_list as $key => $value) {
            $goods_list[$key]['image'] = SITE_URL.$value['original_img'];
        }

        $list['goods_list'] = $goods_list;
        $i = 1;
        //菜单
        foreach ($filter_menu as $k => $v) { // 依照app端的要求 去掉 键名
            $v['name'] = $v['text'];
            unset($v['text']);
            $list['filter_menu'][] = $v;  // 帅选规格
        }

        // 规格
        foreach ($filter_spec as $k => $v) { // 依照app端的要求 去掉 键名
            $items['name'] = $v['name'];
            foreach ($v['item'] as $k2 => $v2) {
                $items['item'][] = array('name' => $v2['item'], 'href' => $v2['href'], 'id' => $i++);
            }
            $list['filter_spec'][] = $items;
            $items = array();
        }

        // $list['filter_spec'] = $filter_spec;
        // 属性
        foreach ($filter_attr as $k => $v) { // 依照app端的要求 去掉 键名
            $items['name'] = $v['attr_name'];
            foreach ($v['attr_value'] as $k2 => $v2) {
                $items['item'][] = array('name' => $v2['attr_value'], 'href' => $v2['href'], 'id' => $i++);
            }

            $list['filter_attr'][] = $items;
            $items = array();
        }
        // 品牌
        foreach ($filter_brand as $k => $v) { // 依照app端的要求 去掉 键名
            $list['filter_brand'][] = array('name' => $v['name'], 'hreg' => $v['href'], 'id' => $i++);
        }

        // 价格
        foreach ($filter_price as $k => $v) { // 依照app端的要求 去掉 键名
            $list['filter_price'][] = array('name' => $v['value'], 'href' => $v['href'], 'id' => $i++);
        }

        $list['sort'] = $sort;
        $list['sort_asc'] = $sort_asc;
        $sort_asc = 'asc' == $sort_asc ? 'desc' : 'asc';
        $list['orderby_default'] = urldecode(U('Goods/goodsList', $filter_param, '')); // 默认排序
        $list['orderby_sales_sum'] = urldecode(U('Goods/goodsList', array_merge($filter_param, array('sort' => 'sales_sum', 'sort_asc' => 'desc')), '')); // 销量排序
        $list['orderby_price'] = urldecode(U('Goods/goodsList', array_merge($filter_param, array('sort' => 'shop_price', 'sort_asc' => $sort_asc)), '')); // 价格
        $list['orderby_comment_count'] = urldecode(U('Goods/goodsList', array_merge($filter_param, array('sort' => 'comment_count', 'sort_asc' => 'desc')), '')); // 评论
        $list['orderby_is_new'] = urldecode(U('Goods/goodsList', array_merge($filter_param, array('sort' => 'is_new', 'sort_asc' => 'desc')), '')); // 新品
        C('TOKEN_ON', false);

        $json_arr = array('status' => 1, 'msg' => '获取成功', 'result' => $list);
        $json_str = json_encode($json_arr, true);
        exit($json_str);
    }

    /**
     * @author dyr
     * 店铺收藏or取消操作
     */
    public function collectStoreOrNo()
    {
        $store_logic = new StoreLogic();
        $json_arr = $store_logic->collectStoreOrNo($this->user_id, $this->store['store_id']);
        exit(json_encode($json_arr));
    }
}
