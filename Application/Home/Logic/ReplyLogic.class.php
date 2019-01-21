<?php
/**
 
 * ====
 
 * 
 * --------

 
 * ====
 * Author: dyr
 * Date: 2016-08-09
 */

namespace Home\Logic;

use Think\Model\RelationModel;

/**
 * 回复
 * Class CatsLogic
 * @package Home\Logic
 */
class ReplyLogic extends RelationModel{
    /**
     * 把回复树状数组转换成二维数组
     * @param $comment_id 回复id
     * @param int $item_num 条数
     * @return array
     */
    public function getReplyList($comment_id, $item_num = 0)
    {
        $reply_tree = D('reply')->getReplyList($comment_id);
        if(empty($reply_tree)){
            return $reply_tree;
        }
        $reply_flat_list = $this->treeToArray($reply_tree);
        if ($item_num == 0 || count($reply_flat_list) <= $item_num) {
            $res =  $reply_flat_list;
        } else {
            $res =  array_slice($reply_flat_list, $item_num);
        }
        return $res;
    }

    /**
     * 回复分页
     * @param $comment_id
     * @param int $page
     * @param int $item_num
     * @return mixed
     */
    public function getRaplyPage($comment_id, $page = 0, $item_num = 20)
    {
        $reply_tree = D('reply')->getReplyList($comment_id);
        $reply_flat_list = $this->treeToArray($reply_tree);
        $count = count($reply_flat_list);
        $list['list'] = array_slice($reply_flat_list, $page * $item_num, $item_num);
        $list['count'] = $count;
        return $list;
    }

    /**
     * 将树状数组转换二维数组
     * @param $tree
     * @return array
     */
    public function treeToArray($tree) {
        $list = array();
        foreach($tree as $key) {
            $node = $$key['children'];
            unset($key['children']);
            $list[] = $key;
            if($node) $list = array_merge($list, $this->treeToArray($node));
        }
        return $list;
    }
}