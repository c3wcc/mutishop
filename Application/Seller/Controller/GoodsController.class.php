<?php

namespace Seller\Controller;
use Seller\Logic\GoodsLogic;
use Think\AjaxPage;
use Think\Page;

class GoodsController extends BaseController {
   





    /**
     * 商品类型  用于设置商品的属性.
     */
    public function goodsTypeList()
    {
        $model = M('GoodsType');
        $count = $model->count();
        $Page = new Page($count, 100);
        $show = $Page->show();
        $goodsTypeList = $model->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('show', $show);
        $this->assign('goodsTypeList', $goodsTypeList);
        $this->display('goodsTypeList');
    }

    /**
     * 删除商品类型
     */
    public function delGoodsType()
    {
        // 判断 商品规格
        $count = M('spec')->where("type_id = {$_GET['id']}")->count('1');
        $count > 0 && $this->error('该类型下有商品规格不得删除!', U('Seller/Goods/goodsTypeList'));
        // 判断 商品属性
        $count = M('GoodsAttribute')->where("type_id = {$_GET['id']}")->count('1');
        $count > 0 && $this->error('该类型下有商品属性不得删除!', U('Seller/Goods/goodsTypeList'));
        // 删除分类
        M('GoodsType')->where("id = {$_GET['id']}")->delete();
        $this->success('操作成功!!!', U('Seller/Goods/goodsTypeList'));
    }


    /**
     * 添加修改编辑  商品属性类型.
     */
    public function addEditGoodsType()
    {
        $_GET['id'] = $_GET['id'] ? $_GET['id'] : 0;
        $model = M('GoodsType');
        if (IS_POST) {
            $model->create();
            if ($_GET['id']) {
                $model->save();
            } else {
                $model->add();
            }

            $this->success('操作成功!!!', U('Seller/Goods/goodsTypeList'));
            exit;
        }
        $goodsType = $model->find($_GET['id']);
        $this->assign('goodsType', $goodsType);
        $this->display('_goodsType');
    }


     
    /**
     * 商品规格列表    
     */
    public function specList(){               
        $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name,parent_id'); // 已经改成联动菜单                
        $this->assign('cat_list',$cat_list);        
        $this->display();
    }
    

     /**
     *  商品规格列表
     */
    public function ajaxSpecList(){ 
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 '; // 搜索条件                        
        I('cat_id1')   && $where = "$where and cat_id1 = ".I('cat_id1') ;        
        // 关键词搜索               
        $model = D('spec');
        $count = $model->where($where)->where(array('store_id'=>STORE_ID))->count();
        $Page       = new AjaxPage($count,13);
        $show = $Page->show();
        
        $cat_list = M('goods_category')->getField('id,name'); // 已经改成联动菜单        
        $specList = $model->where($where)->order('`cat_id1` desc')->where(array('store_id'=>STORE_ID))->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('cat_list',$cat_list);
   
        $this->assign('specList',$specList);
        $this->assign('page',$show);// 赋值分页输出                        
        $this->display();         
    }      


    /*
     * 删除商品规格
     */
    public function delGoodsSpec()
    {
        // 判断 商品规格项
        $count = M('SpecItem')->where("spec_id = {$_GET['id']}")->count('1');
        $count > 0 && $this->error('清空规格项后才可以删除!', U('Seller/Goods/specList'));
        // 删除分类
        M('Spec')->where("id = {$_GET['id']}")->delete();
        $this->success('操作成功!!!', U('Seller/Goods/specList'));
    }

     /**
     * 添加修改编辑  商品规格
     */
    public  function addEditSpec(){
                        
        
        $model = D("spec");                      
        $type = $_POST['id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新             
        if(($_GET['is_ajax'] == 1) && IS_POST)//ajax提交验证
        {            
            //cat_id1    cat_id2   cat_id3  不能为空
            if($_POST['cat_id1'] == 0  || $_POST['cat_id2'] == 0   || $_POST['cat_id3'] == 0 ){
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '所属分类要全部选择',
                );                   
                $this->ajaxReturn(json_encode($return_arr));
            }

            C('TOKEN_ON',false);
            if(!$model->create(NULL,$type))// 根据表单提交的POST数据创建数据对象                 
            {
                //  编辑
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '操作失败',
                    'data'  => $model->getError(),
                );                   
                $this->ajaxReturn(json_encode($return_arr));
            }else {                   
               // C('TOKEN_ON',true); //  form表单提交
                if ($type == 2)
                {
                    $model->save(); // 写入数据到数据库     
                    $model->afterSave($_POST['id']);						
                }
                else
                {
                    $insert_id = $model->add(); // 写入数据到数据库 
                    $model->afterSave($insert_id);						
                }                    
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',                        
                    'data'  => array('url'=>U('Seller/Goods/specList')),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }  
        }                
       // 点击过来编辑时                 
       $id = $_GET['id'] ? $_GET['id'] : 0;       
       $spec = $model->where("id = $id")->find();  
       $GoodsLogic = new GoodsLogic();  
       $items = $GoodsLogic->getSpecItem($id);
       $spec[items] = implode(PHP_EOL, $items);		   
       $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name,parent_id'); // 已经改成联动菜单
       $this->assign('cat_list',$cat_list);
       $this->assign('spec',$spec);  
       
       $goodsTypeList = M('GoodsType')->select();
       $this->assign('goodsTypeList', $goodsTypeList);

       $this->assign('store_id', STORE_ID);

       $this->display('_spec');           
}  


    /**
     * 获取商品分类 的帅选规格 复选框
     */
    public function ajaxGetSpecList(){
        $GoodsLogic = new GoodsLogic();
        $_REQUEST['category_id'] = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
        $filter_spec = M('GoodsCategory')->where("id = ".$_REQUEST['category_id'])->getField('filter_spec');        
        $filter_spec_arr = explode(',',$filter_spec);        
        $str = $GoodsLogic->GetSpecCheckboxList($_REQUEST['type_id'],$filter_spec_arr);  
        $str = $str ? $str : '没有可帅选的商品规格';
        exit($str);        
    }
 
    /**
     * 获取商品分类 的帅选属性 复选框
     */
    public function ajaxGetAttrList(){
        $GoodsLogic = new GoodsLogic();
        $_REQUEST['category_id'] = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
        $filter_attr = M('GoodsCategory')->where("id = ".$_REQUEST['category_id'])->getField('filter_attr');        
        $filter_attr_arr = explode(',',$filter_attr);        
        $str = $GoodsLogic->GetAttrCheckboxList($_REQUEST['type_id'],$filter_attr_arr);          
        $str = $str ? $str : '没有可帅选的商品属性';
        exit($str);        
    }    
    
    /**
     * 删除分类
     */
    public function delGoodsCategory(){
        // 判断子分类
        $GoodsCategory = M("GoodsCategory");                
        $count = $GoodsCategory->where("parent_id = {$_GET['id']}")->count("id");   
        $count > 0 && $this->error('该分类下还有分类不得删除!',U('Admin/Goods/categoryList'));
        // 判断是否存在商品
        $goods_count = M('Goods')->where("cat_id = {$_GET['id']}")->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!',U('Admin/Goods/categoryList'));
        // 删除分类
        $GoodsCategory->where("id = {$_GET['id']}")->delete();   
        $this->success("操作成功!!!",U('Admin/Goods/categoryList'));
    }
    
    
    /**
     *  商品列表
     */
    public function goodsList(){
        checkIsBack();
        $store_goods_class_list = M('store_goods_class')->where("parent_id = 0 and store_id = ".STORE_ID)->select();
        $this->assign('store_goods_class_list',$store_goods_class_list);        
        $this->display();                                           
    }
    
    /**
     *  商品列表
     */
    public function ajaxGoodsList(){         
 
        $where = " deleted = 0 and store_id = ".STORE_ID; // 搜索条件                
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;        
        (I('store_cat_id1') !== '') && $where = "$where and store_cat_id1 = ".I('store_cat_id1');
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale');
        
        // $goods_state = I('goods_state'); // 商品状态  0待审核 1审核通过 2审核失败  3违规下架        
        // if($goods_state){
        //     $where = "$where and goods_state in ($goods_state) ";
        // }
        
        // 关键词搜索               
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%')" ;
        }

        $model = M('Goods');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);
        
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
         
        //是否从缓存中获取Page
        if(session('is_back')==1){
            $Page = getPageFromCache();
            //重置获取条件
            delIsBack();
        }
        
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();
 
        cachePage($Page);
        $show = $Page->show();
        
        $catList = D('goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();         
    }    
    
     /**
     * 添加修改商品
     */
    public function addEditGoods_admin(){
        
        $GoodsLogic = new GoodsLogic();                         
        $Goods = D('Goods'); //
        $type = $_POST['goods_id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新                        
        //ajax提交验证
        if(($_GET['is_ajax'] == 1) && IS_POST)
        {                
            C('TOKEN_ON',false);
            if(!$Goods->create(NULL,$type))// 根据表单提交的POST数据创建数据对象                 
            {
                //  编辑
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '操作失败',
                    'data'  => $Goods->getError(),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }else {
                //  form表单提交
               // C('TOKEN_ON',true);                                                            
                $Goods->on_time = time(); // 上架时间
                //$Goods->cat_id = $_POST['cat_id_1'];
                //$_POST['cat_id_2'] && ($Goods->cat_id = $_POST['cat_id_2']);
                //$_POST['cat_id_3'] && ($Goods->cat_id = $_POST['cat_id_3']);

                $_POST['extend_cat_id_2'] && ($Goods->extend_cat_id = $_POST['extend_cat_id_2']);
                $_POST['extend_cat_id_3'] && ($Goods->extend_cat_id = $_POST['extend_cat_id_3']);                                        
                
                if ($type == 2)
                {
                    $goods_id = $_POST['goods_id'];   
                    $store_id = M('Goods')->where('goods_id',$goods_id)->getField('store_id');                                             
                    $Goods->save(); // 写入数据到数据库                        
                    $Goods->afterSave($goods_id,$store_id);
                }
                else
                {                           
                    $goods_id = $insert_id = $Goods->add(); // 写入数据到数据库
                    $Goods->afterSave($goods_id);
                }                                        
                if(!$store_id){
                    $store_id = M('Goods')->where('goods_id',$goods_id)->getField('store_id');  
                }                    
                $GoodsLogic->saveGoodsAttr($goods_id, $_POST['goods_type'],$store_id); // 处理商品 属性
                
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',                        
                    'data'  => array('url'=>U('Admin/Goods/goodsList')),
                );
                $this->ajaxReturn(json_encode($return_arr));
            }  
        }
        
        $goodsInfo = D('Goods')->where('goods_id='.I('GET.id',0))->find();
        //$cat_list = $GoodsLogic->goods_cat_list(); // 已经改成联动菜单            
        $level_cat2 = $GoodsLogic->find_parent_cat($goodsInfo['extend_cat_id']); // 获取分类默认选中的下拉框
        $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
        $brandList = $GoodsLogic->getSortBrands();
        $goodsType = M("GoodsType")->select();            
        $this->assign('level_cat',$level_cat);
        $this->assign('level_cat2',$level_cat2);
        $this->assign('cat_list',$cat_list);
        $this->assign('brandList',$brandList);
        $this->assign('goodsType',$goodsType);
        $this->assign('goodsInfo',$goodsInfo);  // 商品详情            
        $goodsImages = M("GoodsImages")->where('goods_id ='.I('GET.id',0))->select();
        $this->assign('goodsImages',$goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        $this->display('_goods');                                     
} 

   /**
     * 添加修改商品
     */
    public function addEditGoods(){   
  
            $GoodsLogic = new GoodsLogic();                         
            $Goods = D('Admin/Goods'); //
            $goods_id = I('goods_id',0);
            $type = $goods_id > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新                        
            //
            if($goods_id > 0)
            {
                $c = M('goods')->where("goods_id = $goods_id and store_id = ".STORE_ID)->count();
                if($c == 0)  
                    $this->error("非法操作",U('Goods/goodsList'));
            }
                        
            //
            //ajax提交验证
            if(($_GET['is_ajax'] == 1) && IS_POST)
            {                
                C('TOKEN_ON',false);
                if(!$Goods->create(NULL,$type))// 根据表单提交的POST数据创建数据对象                 
                {
                    //  编辑
                    $error = $Goods->getError();
                    $error_msg = array_values($error);
                    $return_arr = array(
                        'status' => -1,
                        'msg' => $error_msg[0],
                        'data' => $error,
                    );
                    $this->ajaxReturn(json_encode($return_arr));
                }else {
                   // form表单提交
                   // C('TOKEN_ON',true);                                                            
                    $Goods->on_time = time(); // 上架时间
                    $cat_id3 = I('cat_id3',0);
                    $_POST['extend_cat_id_2'] && ($Goods->extend_cat_id = I('extend_cat_id_2'));
                    $_POST['extend_cat_id_3'] && ($Goods->extend_cat_id = I('extend_cat_id_3'));
                    $Goods->shipping_area_ids = implode(',',$_POST['shipping_area_ids']);
                    $Goods->shipping_area_ids = $Goods->shipping_area_ids ? $Goods->shipping_area_ids : '';
                    
                    $type_id = M('goods_category')->where("id = $cat_id3")->getField('type_id'); // 找到这个分类对应的type_id
                    $store_goods_examine = M('store')->where(array('store_id'=>STORE_ID))->getField('goods_examine');
                    $Goods->goods_type = $type_id ? $type_id : 0;
                    $Goods->store_id = STORE_ID; // 店家id
                    if($store_goods_examine){
                        $Goods->goods_state = 0; // 待审核
                    }else{
                        $Goods->goods_state = 1; // 出售中
                    }

                    if($Goods->distribut > ($Goods->shop_price / 2))
                        $this->ajaxReturn(json_encode(array('status' => -1,'msg'=> '分销的分成金额不得超过商品金额的50%','data'  =>'')));
                    
                    if ($type == 2)
                    {    
                    	if(M('Goods')->where(array('goods_id'=>$goods_id,'store_id'=>STORE_ID))->count()>0){
                            // 修改商品后购物车的商品价格也修改一下
                            M('cart')->where("goods_id = $goods_id and spec_key = ''")->save(array(
                                    'market_price'=>$_POST['market_price'], //市场价
                                    'goods_price'=>$_POST['shop_price'], // 本店价
                                    'member_goods_price'=>$_POST['shop_price'], // 会员折扣价                        
                                    ));                            
                    		$Goods->save(); // 编辑数据到数据库
                    	}else{
                    		$this->ajaxReturn(array('status' => -1,'msg'=> '非法操作'),'JSON');
                    	}                                                                                             
                    }
                    else
                    {                           
                        $goods_id = $Goods->add(); // 新增数据到数据库                        
                    }                                        
                 
                    $Goods->afterSave($goods_id,STORE_ID);                                        
                    $GoodsLogic->saveGoodsAttr($goods_id,$type_id,STORE_ID); // 处理商品 属性
                    
                    $return_arr = array(
                        'status' => 1,
                        'msg'   => '操作成功',                        
                        'data'  => array('url'=>U('Goods/goodsList')),
                    );
                   //重定向, 调整之前URL是设置参数获取方式 
                   session("is_back" , 1);
                   $this->ajaxReturn(json_encode($return_arr));
                    
                }  
            }else{
                
            }
            
            $goodsInfo =M('Goods')->where('goods_id='.I('GET.goods_id',0))->find();  
            $store = M('store')->where(array('store_id'=>STORE_ID))->find();
            if($store['bind_all_gc'] == 1){
            	$cat_list = M('goods_category')->where("parent_id = 0")->select();//自营店已绑定所有分类
            }else{
            	$cat_list = M('goods_category')->where("parent_id = 0 and id in(select class_1 from ".C('DB_PREFIX')."store_bind_class  where store_id = ".STORE_ID." and state = 1 )")->select();//自营店已绑定所有分类
            }                     
            $store_goods_class_list = M('store_goods_class')->where("parent_id = 0 and store_id = ".STORE_ID)->select(); //店铺内部分类                      
            $brandList = $GoodsLogic->getSortBrands();
            $goodsType = M("GoodsType")->select();
            $suppliersList = M("suppliers")->select();
            $plugin_shipping = M('plugin')->where(array('type'=>array('eq','shipping')))->select();//插件物流
            $shipping_area = D('shipping_area')->getShippingArea(STORE_ID);//配送区域
            $goods_shipping_area_ids = explode(',',$goodsInfo['shipping_area_ids']);
            $this->assign('goods_shipping_area_ids',$goods_shipping_area_ids);
            $this->assign('shipping_area',$shipping_area);
            $this->assign('plugin_shipping',$plugin_shipping);
            $this->assign('cat_list',$cat_list);
            $this->assign('store_goods_class_list',$store_goods_class_list);
            $this->assign('brandList',$brandList);
            $this->assign('goodsType',$goodsType);
            $this->assign('suppliersList',$suppliersList);
            $this->assign('goodsInfo',$goodsInfo);  // 商品详情            
            $goodsImages = M("GoodsImages")->where('goods_id ='.I('GET.goods_id',0))->select();
            $this->assign('goodsImages',$goodsImages);  // 商品相册
            $this->initEditor(); // 编辑器
            $this->display('_goods');                                     
    } 
      
    /**
     * 更改指定表的指定字段
     */
    public function updateField(){
        $primary = array(
                'goods' => 'goods_id',                                
                'goods_attribute' => 'attr_id',
        	'ad' =>'ad_id',     
        );
        $id = I('id',0);
        $field = I('field');
        $value= I('value');      
        M($_POST['table'])->where("{$primary[$_POST['table']]} = $id and store_id = ".STORE_ID)->save(array($field =>$value));
        $return_arr = array(
            'status' => 1,
            'msg'   => '操作成功',                        
            'data'  => array('url'=>U('Goods/goodsAttributeList')),
        );
        $this->ajaxReturn(json_encode($return_arr));
    }
    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput(){
        $cat_id3 = I('cat_id3',0);
        $goods_id = I('goods_id',0);
        empty($cat_id3) && exit('');       
        $type_id = M('goods_category')->where("id = $cat_id3")->getField('type_id'); // 找到这个分类对应的type_id
        empty($type_id) && exit('');
        $GoodsLogic = new GoodsLogic();
        $str = $GoodsLogic->getAttrInput($goods_id,$type_id);
        exit($str);
    }
        
    /**
     * 删除商品
     */
    public function delGoods()
    {
        $goods_id = $_GET['id'];
        $error = '';
        
        // 判断此商品是否有订单
        $c1 = M('OrderGoods')->where("goods_id = $goods_id")->count('1');
        $c1 && $error .= '此商品有订单,不得删除! <br/>';
        
        
         // 商品团购
        $c1 = M('group_buy')->where("goods_id = $goods_id")->count('1');
        $c1 && $error .= '此商品有团购,不得删除! <br/>';   
        
         // 商品退货记录
        $c1 = M('return_goods')->where("goods_id = $goods_id")->count('1');
        $c1 && $error .= '此商品有退货记录,不得删除! <br/>';
        
        if($error)
        {
            //假删
            //M("Goods")->where('goods_id ='.$goods_id)->save(array('deleted'=>1));  //商品表
           // $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);
            
           $return_arr = array('status' => -1,'msg' =>$error,'data'  =>'',);   
            $this->ajaxReturn(json_encode($return_arr));            
        }
        
        // 删除此商品        
        $result = M("Goods")->where("goods_id = $goods_id and store_id = ".STORE_ID)->delete();  //商品表
        if($result)
        {
            M("cart")->where('goods_id ='.$goods_id)->delete();  // 购物车
            M("comment")->where('goods_id ='.$goods_id)->delete();  //商品评论
            M("goods_consult")->where('goods_id ='.$goods_id)->delete();  //商品咨询
            M("goods_images")->where('goods_id ='.$goods_id)->delete();  //商品相册
            M("spec_goods_price")->where('goods_id ='.$goods_id)->delete();  //商品规格
            M("spec_image")->where('goods_id ='.$goods_id)->delete();  //商品规格图片
            M("goods_attr")->where('goods_id ='.$goods_id)->delete();  //商品属性     
            M("goods_collect")->where('goods_id ='.$goods_id)->delete();  //商品收藏          
        }            
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
        $this->ajaxReturn(json_encode($return_arr));
    }
  
    /**
     * ajax 获取 品牌列表
     */
    public function getBrandByCat(){
        $db_prefix = C('DB_PREFIX');
        $cat_id = I('cat_id');
        $level = I('l');
        $type_id = I('type_id');        
        
        if($type_id)
            $list = M('brand')->join("left join {$db_prefix}brand_type on {$db_prefix}brand.id = {$db_prefix}brand_type.brand_id and  type_id = $type_id")->order('id')->select();    
        else    
            $list = M('brand')->order('id')->select();        
        
        $goods_category_list = M('goods_category')->where("id in(select cat_id1 from {$db_prefix}brand) ")->getField("id,name,parent_id");
        $goods_category_list[0] = array('id'=>0, 'name'=>'默认');
        asort($goods_category_list);
        $this->assign('goods_category_list',$goods_category_list);        
        $this->assign('type_id',$type_id);
        $this->assign('list',$list);
        $this->display();
    }
    
    
    /**
     * ajax 获取 规格列表
     */
    public function getSpecByCat(){
        
        $db_prefix = C('DB_PREFIX');
        $cat_id = I('cat_id');
        $level = I('l');
        $type_id = I('type_id');
       
        if($type_id)            
            $list = M('spec')->join("left join {$db_prefix}spec_type on {$db_prefix}spec.id = {$db_prefix}spec_type.spec_id  and  type_id = $type_id")->order('id')->select();
        else    
            $list = M('spec')->order('id')->select();        
                       
        $goods_category_list = M('goods_category')->where("id in(select cat_id1 from {$db_prefix}spec) ")->getField("id,name,parent_id");
        $goods_category_list[0] = array('id'=>0, 'name'=>'默认');
        asort($goods_category_list);               
        $this->assign('goods_category_list',$goods_category_list);
        $this->assign('type_id',$type_id);
        $this->assign('list',$list);
        $this->display();
    }    
     
    /**
     * 初始化编辑器链接     
     * 本编辑器参考 地址 http://fex.baidu.com/ueditor/
     */
    private function initEditor()
    {
        $this->assign("URL_upload", U('Admin/Ueditor/imageUp',array('savepath'=>'goods'))); // 图片上传目录
        $this->assign("URL_imageUp", U('Admin/Ueditor/imageUp',array('savepath'=>'article'))); //  不知道啥图片
        $this->assign("URL_fileUp", U('Admin/Ueditor/fileUp',array('savepath'=>'article'))); // 文件上传s
        $this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp',array('savepath'=>'article')));  //  图片流
        $this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage',array('savepath'=>'article'))); // 远程图片管理
        $this->assign("URL_imageManager", U('Admin/Ueditor/imageManager',array('savepath'=>'article'))); // 图片管理        
        $this->assign("URL_getMovie", U('Admin/Ueditor/getMovie',array('savepath'=>'article'))); // 视频上传
        $this->assign("URL_Home", "");
    }    
     
    

    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
       
        $goods_id = I('goods_id',0);
        $spec_type = I('spec_type',0);
        empty($spec_type) && exit('spec_type为空');
        $goods_id = $goods_id ? $goods_id : 0;

        $type_id = M('goods_category')->where("id = $spec_type")->getField('type_id'); // 找到这个分类对应的type_id
        empty($type_id) && exit('type_id为空');
        $spec_id_arr = M('spec_type')->where("type_id = $type_id")->getField('spec_id',true); // 找出这个类型的 所有 规格id 
        empty($spec_id_arr) && exit('');
        
        $specList = D('Spec')->where("id in (".  implode(',',$spec_id_arr).") ")->order('`order` desc')->select(); // 找出这个类型的所有规格
        foreach($specList as $k => $v)        
            $specList[$k]['spec_item'] = D('SpecItem')->where("store_id = ".STORE_ID." and spec_id = ".$v['id'])->getField('id,item'); // 获取规格项                
        
        $items_id = M('SpecGoodsPrice')->where("goods_id = $goods_id")->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);       
        
        // 获取商品规格图片                
        if($goods_id)
        {
           $specImageList = M('SpecImage')->where("goods_id = $goods_id")->getField('spec_image_id,src');                 
        }        
        $this->assign('specImageList',$specImageList);
        
        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        $this->display('ajax_spec_select');        
    }    
    
    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */    
    public function ajaxGetSpecInput(){     
         $GoodsLogic = new GoodsLogic();
         $goods_id = I('get.goods_id',0);
         $spec_arr = I('spec_arr');
         $str = $GoodsLogic->getSpecInput($goods_id ,$spec_arr,STORE_ID);
         exit($str);   
    }
    
    /**
     * 商家发布商品时添加的规格
     */
    public function addSpecItem(){
        $spec_id = I('spec_id',0); // 规格id
        $spec_item = I('spec_item','','trim');// 规格项
        
        $c = M('spec_item')->where("store_id = ".STORE_ID." and item = '$spec_item' and spec_id = $spec_id")->count();
        if($c > 0)
        {
            $return_arr = array(
                'status' => -1,
                'msg'   => '规格已经存在',                        
                'data'  =>'',
             );             
             exit(json_encode($return_arr));
        }        
        $data = array(
            'spec_id'=>$spec_id,
            'item'=>$spec_item,
            'store_id'=>STORE_ID,
        );
        M('spec_item')->add($data);
        
        $return_arr = array(
            'status' => 1,
            'msg'   => '添加成功!',                        
            'data'  =>'',
         );
         exit(json_encode($return_arr));         
    }
    
    /**
     * 商家发布商品时删除的规格
     */
    public function delSpecItem(){
        $spec_id = I('spec_id',0); // 规格id
        $spec_item = I('spec_item','','trim');// 规格项
        $spec_item_id = I('spec_item_id',0); //规格项 id
        
        if(!empty($spec_item_id))
            $id = $spec_item_id;
        else    
            $id = M('spec_item')->where("store_id = ".STORE_ID." and item = '$spec_item' and spec_id = $spec_id")->getField('id');
        
        if(empty($id))
        {
             $return_arr = array( 'status' => -1, 'msg' => '规格不存在');
             exit(json_encode($return_arr));
        }        
        $c = M("SpecGoodsPrice")->where("store_id = ".STORE_ID." and `key` REGEXP '^{$id}_' OR `key` REGEXP '_{$id}_' OR `key` REGEXP '_{$id}$' or `key` = '{$id}'")->count(); // 其他商品用到这个规格不得删除
        if($c)
        {
            $return_arr = array('status' => -1,'msg'=> '此规格其他商品使用中,不得删除');             
             exit(json_encode($return_arr));
        }                        
        M('spec_item')->where("id = $id")->delete(); // 删除规格项
        M('spec_image')->where("spec_image_id = $id and store_id = ".STORE_ID)->delete(); // 删除规格图片选项
        $return_arr = array('status' => 1,'msg'=> '删除成功!');
        exit(json_encode($return_arr));         
    }    
    // /**
    //  * 商品规格列表    
    //  */
    // public function specList(){               
    //     $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name,parent_id'); // 已经改成联动菜单                
    //     $this->assign('cat_list',$cat_list);        
    //     $this->display();
    // }  
    
    /**
     *  商品规格列表
     */
    // public function ajaxSpecList(){ 
	// $where = ' 1 = 1 '; // 搜索条件                        
    //     I('cat_id1')   && $where = "$where and cat_id3 = ".I('cat_id3') ;        
    //     // 关键词搜索               
    //     $model = D('spec');
    //     $count = $model->where($where)->count();
    //     $Page       = new AjaxPage($count,13);
    //     $show = $Page->show();
        
    //     $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name'); // 已经改成联动菜单        
    //     $specList = $model->where($where)->order('`cat_id1` desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    //     $this->assign('spec_id',$spec_id);
    //     $this->assign('specList',$specList);
    //     $this->assign('specItemList',$specItemList);
    //     $this->display();         
    // }  
    /**
     *  批量添加修改规格
     */
    public function batchAddSpecItem(){
        $spec_id = I('spec_id',0);
        $item = I('item'); 
        $spec_item = M('spec_item')->where("store_id = ".STORE_ID."  and spec_id = $spec_id")->getField('id,item');        
        foreach($item as $k => $v)
        {            
            $v = trim($v);
            if(empty($v)) continue; // 值不存在 则跳过不处理            
            // 如果spec_id 存在 并且 值不相等 说明值被改动过
            if(array_key_exists($k,$spec_item) && $v != $spec_item[$k])
            {
                M('spec_item')->where("id = $k and store_id = ".STORE_ID)->save(array('item'=>$v));
                // 如果这个key不存在 并且规格项也不存在 说明 需要插入
            }elseif(!array_key_exists($k,$spec_item) && !in_array($v, $spec_item)){
                M('spec_item')->add(array('spec_id'=>$spec_id,'item'=>$v,'store_id'=>STORE_ID));
            }
        }
        $this->success('操作成功!');
    }  
    
        /**
         * 品牌列表
         */
        public function brandList(){  
            $model = M("Brand"); 
            $where = " store_id = ".STORE_ID;
            $keyword = I('keyword');
            $keyword && $where.=" and name like '%$keyword%' ";
            $count = $model->where($where)->count();
            $Page  = new Page($count,16);
            $brandList = $model->where($where)->order("`sort` asc")->limit($Page->firstRow.','.$Page->listRows)->select();        
            $show  = $Page->show(); 
            $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name'); // 已经改成联动菜单
            $this->assign('cat_list',$cat_list);       
            $this->assign('show',$show);
            $this->assign('brandList',$brandList);
            $this->display('brandList');
        }
	
    /**
     * 添加修改编辑  商品品牌
     */
    public  function addEditBrand(){        
            $id = I('id',0);
            $model = M("Brand");           
            if(IS_POST)
            {
                    $model->create();                    
                    if($id){
                        $model->save();
                    }                        
                    else
                    {
                        $model->store_id = STORE_ID;
                        $model->status = 1;
                        $id = $model->add();
                    }                        
                    $this->success("操作成功!!!",U('Goods/brandList',array('p'=>$_GET['p'])));               
                    exit;
            }           
           $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
           $this->assign('cat_list',$cat_list);
           $brand = $model->where("id = $id")->find();           
           $this->assign('brand',$brand);
           $this->display('_brand');           
    }    
    
    /**
     * 删除品牌
     */
    public function delBrand()
    {           
        $model = M("Brand"); 
        $id = I('id');
        $model->where("id = $id and store_id = ".STORE_ID)->delete();
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
        $this->ajaxReturn(json_encode($return_arr));
    }    
    
	public function brand_info(){
		$id = I('id',0);
		$cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
		$this->assign('cat_list',$cat_list);
		if($id>0){
			$brand = M("Brand")->find($id);
			$this->assign('brand',$brand);
		}
		$this->display();
	}
	
	public function brand_save(){
		$data = I('post.');
		if($data['act'] == 'del'){
			$goods_count = M('Goods')->where("brand_id = {$data['id']}")->count('1');
			if($goods_count) respose(array('stat'=>'fail','msg' =>'此品牌有商品在用不得删除!'));
			$r = M('brand')->where('id='.$data['id'])->delete();
			if($r){
				respose(array('stat'=>'ok'));
			}else{
				respose(array('stat'=>'fail','msg'=>'操作失败'));
			}
		}else{
			if(empty($data['id'])){
				$data['store_id'] = STORE_ID;
				$r = M('brand')->add($data);
			}else{
				$r = M('brand')->where('id='.$data['id'])->save($data);
			}
		}
		if($r){
			$this->success("操作成功",U('Store/brand_list'));
		}else{
			$this->error("操作失败",U('Store/brand_list'));
		}
	}    
        
    /**
     * 删除商品相册图
     */
    public function del_goods_images()
    {
        $path = I('filename','');
        $goods_images = M('goods_images')->where(array('image_url'=>$path))->select();
        foreach($goods_images as $key=>$val)
        {
            $goods = M('goods')->where(array('goods_id'=>$goods_images[$key]['goods_id']))->find();
            if($goods['store_id'] == STORE_ID){
                M('goods_images')->where(array('img_id'=>$goods_images[$key]['img_id']))->delete();
            }
        }
    }
}