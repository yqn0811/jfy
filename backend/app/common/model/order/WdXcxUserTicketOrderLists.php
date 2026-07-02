<?php

namespace app\common\model\order;

use app\common\model\appointment\WdXcxAppointmentContent;
use app\common\model\gift_exchange\WdXcxScoreShop;
use app\common\model\user\WdXcxUser;
use app\common\service\ylb\YlbApiService;
use think\Model;

class WdXcxUserTicketOrderLists extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_ticket_order_lists';
    protected $autoWriteTimestamp = true;
    
    protected $type = [
        'ticket_lable' => 'array',
        'pay_info' => 'array',
        'ticket_details_list' => 'array',
    ];

    public function getTicketLableAttr($value)
    {
        if($value){
            $value = json_decode($value, true);
            $value = array_filter($value, function($v){
                return $v ? true : false;
            });
            return $value;
        }else{
            return [];
        }
    }

    public function user()
    {
        return $this->hasOne(WdXcxUser::class, 'id', 'user_id');
    }

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    public function getTicketThumbAttr($value)
    {
        return $value ? $value : getLocalImage('/image/default/default_ticket_thumb.png');
    }

    public function getStatusAttr($value)
    {
        if($value == 1){
            if($this->ticket_past_time < time()){
                $this->where('id', $this->id)->update(['status' => 3]);
                WdXcxUserOrderLists::where('orderform_id', $this->id)->update(['status' => 3]);
                $value = 3;
            }
            if($this->user){
                //查询作废的套票
                $user_tickets = (new YlbApiService())->getUserTicketLists($this->user->leaguer_id, 'False');
                if(count($user_tickets) > 0){
                    $user_tickets_codes = array_column($user_tickets, 'TicketId');
                    if(in_array($this->ticket_code, $user_tickets_codes)){
                        $this->where('id', $this->id)->update(['status' => 3]);
                        WdXcxUserOrderLists::where('orderform_id', $this->id)->update(['status' => 3]);
                        $value = 3;
                    }
                }
            }

            //检查是否使用
//            $user_tickets = (new YlbApiService())->getUserTicketLists($this->user->leaguer_id);
//            $user_tickets_codes = array_column($user_tickets, 'TicketId');
//            $k = array_search($this->ticket_code, $user_tickets_codes);
//            if($k !== false){
//                $ticket_info = $user_tickets[$k];
//                if($ticket_info['Status'] == 1){
//                    $this->where('id', $this->id)->update([
//                        'status' => 2,
//                        'ticket_past_time' => strtotime($ticket_info['PastDueTime']),
//                    ]);
//                    WdXcxUserOrderLists::where('orderform_id', $this->id)->update(['status' => 2]);
//                    $value = 2;
//                }
//            }else{
//                $this->where('id', $this->id)->update(['status' => 3]);
//                WdXcxUserOrderLists::where('orderform_id', $this->id)->update(['status' => 3]);
//                $value = 3;
//            }
        }
        if($value == 2){
            if($this->ticket_past_time < time()){
                $this->where('id', $this->id)->update(['status' => 3]);
                WdXcxUserOrderLists::where('orderform_id', $this->id)->update(['status' => 3]);
                $value = 3;
            }
        }
        return $value;
    }

    /**套票消费通知修改状态
     * @param $user_id
     * @param $ticket_code
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeTicketUseStatus($user_id, $ticket_code)
    {
        $ticket_order = $this->where('user_id', $user_id)
            ->where('ticket_code', $ticket_code)
            ->where('status', 1)
            ->find();
        if($ticket_order){
            $leaguerID = WdXcxUser::where('id', $user_id)->value('leaguer_id');
            $user_ticket = (new YlbApiService())->getUserTicketLists($leaguerID);
            if($user_ticket){
                foreach ($user_ticket as $item){
                    if($item['TicketId'] == $ticket_code){
                        $this->where('id', $ticket_order->id)->update([
                            'status' => 2,
                            'ticket_past_time' => strtotime($item['PastDueTime']),
                        ]);
                        WdXcxUserOrderLists::where('orderform_id', $ticket_order->id)->update(['status' => 2]);
                    }
                }

            }
        }
    }

}