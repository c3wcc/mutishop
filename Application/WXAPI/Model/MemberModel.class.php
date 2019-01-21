<?php


namespace WXAPI\Model;

use Think\Model;

/**
 * Class UserModel
 * @package Admin\Model
 */
class MemberModel extends Model//Relation
{
    protected $trueTableName = 'lp_member_user';
    protected $dbName = 'ty_logistics_platform';
}