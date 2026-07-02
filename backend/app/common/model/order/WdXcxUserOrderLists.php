<?php

namespace app\common\model\order;

use think\facade\Db;
use think\Model;

class WdXcxUserOrderLists extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_order_lists';
    protected $autoWriteTimestamp = true;

    const ORDER_TYPE_TICKET = 'ticket'; //通票订单
    const ORDER_TYPE_EXCHANGE = 'exchange'; //兑换订单
    const ORDER_TYPE_RECHARGE = 'recharge'; //充值订单
    const ORDER_TYPE_BUY_GRADE = 'buy_grade'; //购买会员订单
    const ORDER_TYPE_CATERING = 'catering'; //点餐订单
    const ORDER_TYPE_RESERVE = 'reserve'; //预约订单
    const ORDER_TYPE_GAMECOIN = 'gamecoin'; //游戏币订单

    const ORDER_STAUS_1 = 1; //待使用
    const ORDER_STAUS_2 = 2; //已使用
    const ORDER_STAUS_3 = 3; //已过期


    public function orderform()
    {
        return $this->morphTo('orderform', [
            self::ORDER_TYPE_TICKET => WdXcxUserTicketOrderLists::class,
            self::ORDER_TYPE_EXCHANGE => WdXcxUserExchangeOrderLists::class,
            self::ORDER_TYPE_RECHARGE => WdXcxUserRechargeOrderLists::class,
            self::ORDER_TYPE_BUY_GRADE => WdXcxUserBuyGradeOrderLists::class,
            self::ORDER_TYPE_CATERING => WdXcxUserCateringOrderLists::class,
            self::ORDER_TYPE_RESERVE => WdXcxUserAppointmentOrderLists::class,
            self::ORDER_TYPE_GAMECOIN => WdXcxUserGamecoinOrderLists::class,
        ]);
    }

    public function getOrderformData()
    {
        switch ($this->orderform_type){
            case self::ORDER_TYPE_TICKET:
                return $this->orderform()->field(
                    'id, user_id, ticket_code, ticket_name, ticket_thumb, ticket_lable, create_time, status, ticket_past_time, FROM_UNIXTIME(ticket_past_time, "%Y.%m.%d %H:%i:%s") as ticket_past_time_str'
                )->find();
            case self::ORDER_TYPE_EXCHANGE:
                return $this->orderform()->field(
                    'id, status, create_time, exchange_type, pay_diamond, pay_score, get_score, get_balance, gift_title, gift_thumb, pay_lottery'
                )->find();
            case self::ORDER_TYPE_RECHARGE:
                return $this->orderform()->field(
                    'id, pay_price, give_price, getscore, create_time'
                )->find();
            case self::ORDER_TYPE_BUY_GRADE:
                return $this->orderform()->field(
                    'id, pay_price, grade_level, create_time'
                )->find();
            case self::ORDER_TYPE_CATERING:
                return $this->orderform()->field(
                    'id, status, order_type, order_number, table_number, create_time, order_id, pay_price'
                )->with(['dishes' => function($query){
                    $query->field('id, order_id, dishes_title, dishes_thumb, num');
                }])->withSum('dishes', 'num')->find();
            case self::ORDER_TYPE_RESERVE:
                return $this->orderform()->field(
                    'id, status, pay_score, appointment_cate, appoint_title, appoint_thumb, appoint_time, appoint_date, create_time, appoint_start_time'
                )->find();
            case self::ORDER_TYPE_GAMECOIN:
                return $this->orderform()->field(
                    'id, pay_price, get_gamecoin, create_time'
                )->find();
            default:
                return null;
        }
    }
}