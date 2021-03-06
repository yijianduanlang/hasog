<?php
// +----------------------------------------------------------------------
// | HaSog (幻神商城系统)
// +----------------------------------------------------------------------
// | 技术支持【幻神科技】: https://www.hasog.com
// +----------------------------------------------------------------------
// | 联系我们:  https://www.hasog.com
// +----------------------------------------------------------------------
// | gitee开源项目：https://gitee.com/orzice/hasog
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/orzice/hasog
// +----------------------------------------------------------------------
// | Author：Orzice(小涛)  https://gitee.com/orzice
// +----------------------------------------------------------------------
// | DateTime：2020-12-31 18:22:57
// +----------------------------------------------------------------------

namespace app\common\model;


use app\common\model\TimeModel;
use think\Exception;
use think\model\relation\HasMany;

class Member extends TimeModel
{

    protected $deleteTime = 'delete_time';


    public function orders(){
        return $this->hasMany('app\common\model\Order', 'uid', 'id');
    }

    public function address(){
        return $this->hasMany('app\common\model\MemberAddress', 'uid', 'id');
    }

    public function address_string(){
        $address = $this->address()->select();
        foreach ($address as $item){
            $item->area_name();
        }
//        print_r($address->toArray());die();
        return $address;
    }


    public function carts(){
        return $this->hasMany('app\common\model\Cart', 'uid', 'id');
    }

    public function favors(){
        return $this->hasMany('app\common\model\GoodsFavor', 'uid', 'id');
    }

}