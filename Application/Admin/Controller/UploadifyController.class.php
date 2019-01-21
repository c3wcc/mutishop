<?php

namespace Admin\Controller;

class UploadifyController extends BaseController{

    public function upload(){
        $func = I('func');
        $path = I('path','temp');
		$image_upload_limit_size = C('image_upload_limit_size');
        $fileType = I('fileType','Images');  //上传文件类型，视频，图片

        if($fileType == "undefined"){
            $fileType = "Images";
        }

        if($fileType == 'Flash'){
            $upload = U('Admin/Ueditor/videoUp',array('savepath'=>$path,'pictitle'=>'banner','dir'=>'video'));
            $type = 'mp4,3gp,flv,avi,wmv';
        }else{
            $upload = U('Admin/Ueditor/imageUp',array('savepath'=>$path,'pictitle'=>'banner','dir'=>'images'));
            $type = 'jpg,png,gif,jpeg';
        }
        $info = array(
        	'num'=> I('num/d'),
        	'fileType'=> $fileType,
            'title' => '',
            'upload' =>$upload,
        	'fileList'=>U('Admin/Uploadify/fileList',array('path'=>$path)),
            'size' => $image_upload_limit_size/(1024 * 1024).'M',
            'type' =>$type,
            'input' => I('input'),
            'func' => empty($func) ? 'undefined' : $func,
        );
        
        $this->assign('info',$info);
        $this->display();
    }



    // public function upload(){
    //     $func = I('func');
    //     $path = I('path','temp');
        
    //     $info = array(
    //     	'num'=> I('num'),
    //         'title' => '',       	
    //     	'upload' =>U('Admin/Ueditor/imageUp',array('savepath'=>$path,'pictitle'=>'banner','dir'=>'images')),
    //         'size' => '4M',
    //         'type' =>'jpg,png,gif,jpeg',
    //         'input' => I('input'),
    //         'func' => empty($func) ? 'undefined' : $func,
    //     );
    //     // dump($info);
    //     $this->assign('info',$info);
    //     $this->display();
    // }
    
    /*
              删除上传的图片
     */
    public function delupload(){
        $action=isset($_GET['action']) ? $_GET['action'] : null;
       
        $filename= isset($_GET['filename']) ? $_GET['filename'] : null;
        $filename= str_replace('../','',$filename);
        $filename= trim($filename,'.');
        $filename= trim($filename,'/');
     
        if($action=='del' && !empty($filename)){
            unlink($filename);
            echo 1;
        }
    }

}