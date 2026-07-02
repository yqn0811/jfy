<?php

namespace app\common\service\order;

use app\common\model\order\WdXcxUserOrderLists;
use app\common\model\order\WdXcxUserTicketOrderLists;
use app\common\service\BaseService;
use think\App;
use think\facade\Config;

class OrderService extends BaseService
{
    private $order_model;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->order_model = new WdXcxUserOrderLists();
    }

    /**获取用户所有订单列表
     * @param $param
     * @return \think\Paginator
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserAllOrderLists($param, $user_id)
    {
        $type = $param['type'] ? $param['type'] : 0;
        if(!in_array($type, [0,1,2,3,4])){
            throwError('参数错误');
        }
        $lists = $this->order_model
            ->where('user_id', $user_id)
            ->whereNotIn('orderform_type', [WdXcxUserOrderLists::ORDER_TYPE_BUY_GRADE, WdXcxUserOrderLists::ORDER_TYPE_RECHARGE])
            ->where('uniacid', $this->uniacid)
            ->whereBetween('create_time', [strtotime('-90 days'), time()])
            ->where(function ($query)use($type){
                if($type){
                    if($type == 2){
                        $query->where('status', $type)->where('orderform_type', WdXcxUserOrderLists::ORDER_TYPE_TICKET);
                    }else{
                        $query->where('status', $type);
                    }

                }
            })->withoutField('id, update_time, delete_time')
            ->order('id desc')
            ->paginate(10)->each(function ($item){
                $getOrderformData = $item->getOrderformData;
                if($item->orderform_type == WdXcxUserOrderLists::ORDER_TYPE_TICKET){
                    $status = $getOrderformData->status;
                    $getOrderformData->status = $status;
                    $getOrderformData->ticket_past_time_str = date('Y-m-d H:i:s', WdXcxUserTicketOrderLists::where('id', $getOrderformData->id)->value('ticket_past_time'));
                }
                $item->getOrderformData = $getOrderformData;
                $item->create_time_str = date('Y.m.d H:i:s', strtotime($item->create_time));
                unset($item->create_time);
            });
        return $lists;
    }
}