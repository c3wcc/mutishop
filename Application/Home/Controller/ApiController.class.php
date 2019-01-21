<?php
namespace Home\Controller;

use Think\Controller;

class ApiController extends Controller {
   public function test(){
        header("Content-type: text/html; charset=utf-8");
       
     
        // file_put_contents("pay.txt", '22222', FILE_APPEND);
   }

   /**
    * 登录任务
    */
   public function login_task(){
     
       echo 1;
   }

    /*
     * 获取地区
     */
    public function getRegion(){
        $parent_id = I('get.parent_id');
        $selected = I('get.selected',0);        
        $data = M('region')->where("parent_id=$parent_id")->select();
        $html = '';
        if($data){
            foreach($data as $h){
            	if($h['id'] == $selected){
            		$html .= "<option value='{$h['id']}' selected>{$h['name']}</option>";
            	}
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        echo $html;
    }
    

    public function getTwon(){
    	$parent_id = I('get.parent_id');
    	$data = M('region')->where("parent_id=$parent_id")->select();
    	$html = '';
    	if($data){
    		foreach($data as $h){
    			$html .= "<option value='{$h['id']}'>{$h['name']}</option>";
    		}
    	}
    	if(empty($html)){
    		echo '0';
    	}else{
    		echo $html;
    	}
    }
    
    /*
     * 获取商品分类
     */
    public function get_category(){
        $parent_id = I('get.parent_id','0'); // 商品分类 父id  
        empty($parent_id) && exit('');
        $list = M('goods_category')->where(array('parent_id'=>$parent_id))->select();        
        foreach($list as $k => $v)
        {             
            $html .= "<option value='{$v['id']}' rel='{$v['commission']}'>{$v['name']}</option>";
        }            
        exit($html);
    }
    
     public function get_cates(){
     	$parent_id = I('get.parent_id','0'); // 商品分类 父id
     	empty($parent_id) && exit('');
     	$list = M('goods_category')->where(array('parent_id'=>$parent_id))->select();
     	foreach($list as $k => $v)
     	{
     		$html .= "<input type='checkbox' name='subcate[]' rel='{$v['commission']}' data-name='{$v['name']}' value='{$v['id']}'>".$v['name'];
     	}
     	exit($html);
     }    
    /*
     * 获取店铺内分类
     */
    public function get_store_category(){
        // 店铺id
        $store_id = session('store_id');
        $store_id = $store_id ? $store_id : 0;
        $parent_id = I('get.parent_id',0); // 商品分类 父id  
        
        ($parent_id == 0) && exit(''); 
        
        $list = M('store_goods_class')->where("parent_id = $parent_id and store_id = $store_id")->select();        
        foreach($list as $k => $v)
            $html .= "<option value='{$v['cat_id']}'>{$v['cat_name']}</option>";        
        exit($html);
    }   
    
    /**
     * 检测手机号是否已经存在
     */
    public function issetMobile()
    {
      $mobile = I("mobile",'0');  
      $users = M('users')->where("mobile = '$mobile'")->find();
      if($users)
          exit ('1');
      else 
          exit ('0');      
    }     
    
}