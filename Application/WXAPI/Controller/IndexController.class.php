<?php

namespace WXAPI\Controller;

class IndexController extends BaseController
{
    public function index()
    {
        $this->display();
    }

    /*
     * 获取首页数据
     */
    public function home()
    {
        //获取轮播图
        $data = M('ad')->where('pid = 2')->field(array('ad_link', 'ad_name', 'ad_code', 'pid', 'ad_type', 'ad_value'))->cache(true, TPSHOP_CACHE_TIME)->select();

        //广告地址转换
        foreach ($data as $k => $v) {
            $data[$k]['ad_code'] = SITE_URL.$v['ad_code'];
        }

        //广告2
        //27,28,29,30,31
        $ad[0] = M('ad')->where(array('ad_id' => 52))->find();
        $ad[1] = M('ad')->where(array('ad_id' => 53))->find();
        $ad[2] = M('ad')->where(array('ad_id' => 54))->find();
        $ad[3] = M('ad')->where(array('ad_id' => 55))->find();
        $ad[4] = M('ad')->where(array('ad_id' => 56))->find();

        foreach ($ad as $key => $value) {
            $ad[$key]['ad_code'] = SITE_URL.$value['ad_code'];
        }

        $city = $_GET['city'];
        $city = urldecode($city);

        //有城市
        if (mb_strlen($city, 'utf-8') > 1) {
            $city_id = D('region')->where(array('name' => array('like', '%'.$city.'%')))->getField('id');

            $store_ids = D('store')->where(array('city_id' => $city_id))->field('store_id')->select();

            if ($store_ids) {
                foreach ($store_ids as $value) {
                    $ids[] = $value['store_id'];
                }
                //echo implode(',',$ids);
                $classs = M('store_bind_class')->where(array('store_id' => array('in', implode(',', $ids))))->distinct(true)->field('class_1')->limit(3)->cache(false, TPSHOP_CACHE_TIME)->select();
                foreach ($classs as $value) {
                    $cids[] = $value['class_1'];
                }
                if ($classs) {
                    $category_arr = M('goods_category')->where(array('id' => array('in', implode(',', $cids))))->field('id,name')->cache(false, TPSHOP_CACHE_TIME)->select();
                }
            }
        }

        if (!$category_arr) {
            //暂时只放 id=9

            //获取大分类
            $category_arr = M('goods_category')->where('id=9 and parent_id=0 and is_show=1 ')->order('sort_order desc')->field('id,name')->cache(false, TPSHOP_CACHE_TIME)->select();
            $result = array();

            foreach ($category_arr as $c) {
                $cat_arr = getCatGrandson($c['id']);
                //获取商品
                //$sql = "select goods_name,goods_id,original_img,shop_price from __PREFIX__goods where  cat_id in (".implode(',',$cat_arr).") limit 4";
                //$goods = M()->query($sql);
                if ($cat_arr) {
                    $cat_id = implode(',', $cat_arr);
                }
              
                $condition['is_on_sale'] = array('eq', 1);
                $condition['goods_state'] = array('eq', 1);
                $goodsList = M('goods')->where($condition)->where('cat_id2 in('.$cat_id.') OR cat_id1 in('.$cat_id.') OR cat_id3 in('.$cat_id.') ')->order('sort desc')->limit(16)->cache(false, TPSHOP_CACHE_TIME)->getField('goods_id,goods_name,original_img,shop_price,sales_sum,click_count');

                foreach ($goodsList as $k => $v) {
                    $v['original_img'] = SITE_URL.$v['original_img'];
                    $c['goods_list'][] = $v;
                }
                $result[] = $c;
            }
        } else {
            $result = array();
            foreach ($category_arr as $c) {
                $cat_arr = getCatGrandson($c['id']);
                //获取商品
                //$sql = "select goods_name,goods_id,original_img,shop_price from __PREFIX__goods where  cat_id in (".implode(',',$cat_arr).") limit 4";
                //$goods = M()->query($sql);
                if ($cat_arr) {
                    $cat_id = implode(',', $cat_arr);
                }
                $sids = implode(',', $ids);

                $goodsList = M('goods')->where("is_on_sale = 1 and cat_id1 in($cat_id) and store_id in($sids)")->limit(4)->cache(false, TPSHOP_CACHE_TIME)->getField('goods_id,goods_name,original_img,shop_price,sales_sum,click_count');
                foreach ($goodsList as $k => $v) {
                    $v['original_img'] = SITE_URL.$v['original_img'];
                    $c['goods_list'][] = $v;
                }
                $result[] = $c;
            }
        }

        exit(json_encode(array('status' => 1, 'msg' => '获取成功', 'result' => array('goods' => $result, 'ad' => $data), 'ad' => $ad)));
    }

    /**
     * 获取服务器配置.
     */
    public function getConfig()
    {
        $config_arr = M('config')->select();
        exit(json_encode(array('status' => 1, 'msg' => '获取成功', 'result' => $config_arr)));
    }

    /**
     * 获取插件信息.
     */
    public function getPluginConfig()
    {
        $data = M('plugin')->where("type='payment' OR type='login'")->select();
        $arr = array();
        foreach ($data as $k => $v) {
            unset($data[$k]['config']);
            unset($data[$k]['config']);

            $data[$k]['config_value'] = unserialize($v['config_value']);
            if ($data[$k]['type'] == 'payment') {
                $arr['payment'][] = $data[$k];
            }
            if ($data[$k]['type'] == 'login') {
                $arr['login'][] = $data[$k];
            }
        }
        exit(json_encode(array('status' => 1, 'msg' => '获取成功', 'result' => $arr ? $arr : '')));
    }
}
