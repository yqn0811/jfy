<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\common\model\order\WdXcxUserAppointmentOrderLists;
use app\common\model\order\WdXcxUserOrderLists;
use app\common\service\user\UserService;
use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;

class WdXcxUserWxpayRecord extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_wxpay_record';
    protected $autoWriteTimestamp = true;

    const WX_PAY_CATEGORY_ORDER = 'category_order'; //点餐订单
    const WX_PAY_GAMECOIN_ORDER = 'gamecoin_order'; //游戏币订单
    const WX_PAY_TICKETING_ORDER = 'ticketing_order'; //套票订单
    const WX_PAY_BUY_GRADE_ORDER = 'buy_grade_order'; //购买会员订单


    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    public function getOrderTypeStrAttr()
    {
        $str = '点餐订单';
        switch ($this->order_type){
            case 'category_order':
                $str = '点餐订单';
                break;
            case 'gamecoin_order':
                $str = '游戏币订单';
                break;
            case 'ticketing_order':
                $str = '套票订单';
                break;
            case 'buy_grade_order':
                $str = '购买会员订单';
                break;
            default:
                $str = '点餐订单';
        }
        return $str;
    }

}