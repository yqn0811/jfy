<?php

namespace app\common\model\distribution;

use app\common\model\order\WdXcxUserCateringOrderLists;
use app\common\model\order\WdXcxUserGamecoinOrderLists;
use app\common\model\order\WdXcxUserOrderLists;
use app\common\model\order\WdXcxUserRechargeOrderLists;
use app\common\model\order\WdXcxUserTicketOrderLists;
use app\common\model\user\WdXcxUser;
use think\Model;
use think\model\concern\SoftDelete;

class WdXcxDistributionOrderLists extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_distribution_order_lists';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'order_log' => 'array',
    ];

    /**分销信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDistributionInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->parent_id);
    }

    /**下单用户信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    /**多态关联订单
     * @return \think\model\relation\MorphTo
     */
    public function orderform()
    {
        return $this->morphTo('orderform', [
            WdXcxUserOrderLists::ORDER_TYPE_TICKET => WdXcxUserTicketOrderLists::class,
            WdXcxUserOrderLists::ORDER_TYPE_GAMECOIN => WdXcxUserGamecoinOrderLists::class,
            WdXcxUserOrderLists::ORDER_TYPE_RECHARGE => WdXcxUserRechargeOrderLists::class,
            WdXcxUserOrderLists::ORDER_TYPE_CATERING => WdXcxUserCateringOrderLists::class,

        ]);
    }

    /**获取订单数据
     * @return array|mixed|Model|\think\model\relation\MorphTo|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderformData()
    {
        switch ($this->orderform_type){
            case WdXcxUserOrderLists::ORDER_TYPE_TICKET:
                return $this->orderform()->field(
                    'id, user_id, ticket_code, ticket_name, ticket_thumb, ticket_lable, create_time, status, ticket_past_time, FROM_UNIXTIME(ticket_past_time, "%Y.%m.%d %H:%i:%s") as ticket_past_time_str'
                )->find();
            case WdXcxUserOrderLists::ORDER_TYPE_RECHARGE:
                return $this->orderform()->field(
                    'id, user_id, pay_price, give_price, getscore, create_time'
                )->find();
            case WdXcxUserOrderLists::ORDER_TYPE_CATERING:
                return $this->orderform()->field(
                    'id, user_id, status, order_type, order_number, table_number, create_time, order_id, pay_price'
                )->with(['dishes' => function($query){
                    $query->field('id, order_id, dishes_title, dishes_thumb, num');
                }])->withSum('dishes', 'num')->find();
            case WdXcxUserOrderLists::ORDER_TYPE_GAMECOIN:
                return $this->orderform()->field(
                    'id, user_id, pay_price, get_gamecoin, create_time'
                )->find();
            default:
                return null;
        }
    }

    /**订单类型文字
     * @return string
     */
    public function getOrderTypeStrAttr()
    {
        $str = '订单';
        switch ($this->orderform_type){
            case WdXcxUserOrderLists::ORDER_TYPE_RECHARGE:
                $str = '充值订单';
                break;
            case WdXcxUserOrderLists::ORDER_TYPE_TICKET:
                $str = '套票订单';
                break;
            case WdXcxUserOrderLists::ORDER_TYPE_GAMECOIN:
                $str = '游戏币订单';
                break;
            case WdXcxUserOrderLists::ORDER_TYPE_CATERING:
                $str = '点餐订单';
                break;
            default:
                return '未知';
        }
        return $str;
    }

    /**结算时间
     * @return false|string
     */
    public function getSettleTimeStrAttr()
    {
        if($this->settle_time > 0){
            return date('Y-m-d H:i:s',$this->settle_time);
        }else{
            return '--';
        }
    }

    /**分销商统计数据
     * @param $user_id
     * @param $date_type
     * @param $time_data
     * @return array
     */
    public function getUserDistributionData($user_id, $date_type, $time_data)
    {
        //预估佣金
        $estimate_commission = $this->getOrderMoneyByCondition($user_id, 'commission_money', [0,1,2], $date_type, $time_data);
        //销售订单金额
        $order_money = $this->getOrderMoneyByCondition($user_id, 'order_money', [0,1,2], $date_type, $time_data);
        //销售订单数量
        $order_count = $this->getOrderMoneyByCondition($user_id, '', [0,1,2], $date_type, $time_data, true);
        //待分成
        $to_commission = $this->getOrderMoneyByCondition($user_id, 'commission_money', 1, $date_type, $time_data);
        return compact('estimate_commission', 'order_money', 'order_count', 'to_commission');
    }

    /**根据条件获取分销订单金额
     * @param $user_id int 分销商id
     * @param $sum_field string 要统计的字段
     * @param $status int|array 订单状态
     * @param $date_type int 时间类型
     * @param $time_data array|null 时间数据
     * @param $count bool 是否统计数量
     * @return float|int
     * @throws \think\db\exception\DbException
     */
    private function getOrderMoneyByCondition($user_id, $sum_field, $status, $date_type, $time_data, $count = false)
    {
        $data_sql = $this->where('parent_id', $user_id)
            ->where(function ($query)use($date_type, $time_data, $status){
                if($date_type){
                    $query->whereBetween('create_time', $time_data);
                }
                if(is_array($status)){
                    $query->whereIn('status', $status);
                }else{
                    $query->where('status', $status);
                }
            });
        if($count){
            $result = $data_sql->count();
        }else{
            $result = $data_sql->sum($sum_field);
        }
        return $result;
    }



}