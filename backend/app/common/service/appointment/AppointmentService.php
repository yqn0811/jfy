<?php

namespace app\common\service\appointment;

use app\common\model\appointment\WdXcxAppointmentCate;
use app\common\model\appointment\WdXcxAppointmentContent;
use app\common\model\order\WdXcxUserAppointmentOrderLists;
use app\common\model\order\WdXcxUserOrderLists;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserBalanceRecord;
use app\common\model\user\WdXcxUserGiveBalanceRecord;
use app\common\model\user\WdXcxUserIntegralRecord;
use app\common\service\BaseService;
use cores\utils\Utils;
use think\App;
use think\Exception;
use think\facade\Db;

class AppointmentService extends BaseService
{
    private $cate_model;
    private $appoint_model;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->cate_model = new WdXcxAppointmentCate();
        $this->appoint_model = new WdXcxAppointmentContent();
    }

    /**获取预约分类列表
     * @return WdXcxAppointmentCate[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAppointmentCate()
    {
        return $this->cate_model->where('uniacid', $this->uniacid)
            ->order('sort_num desc,id desc')
            ->field('id, cate_title')
            ->select();
    }

    /**获取预约列表
     * @param $param
     * @return \think\Paginator
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAppointmentCateList($param)
    {
        $cate_id = $param['cate_id'];
        $cate = $this->cate_model->where('id', $cate_id)->find();
        if(!$cate){
            throwError('分类不存在');
        }
        $lists = $this->appoint_model->where('uniacid', $this->uniacid)
            ->where('cate_id', $cate_id)
            ->where('status', 1)
            ->field('id, uniacid, appoint_time_info, appoint_title, appoint_lable, pay_score, appoint_thumb, appoint_price, appoint_type_lable')
            ->order('id desc')
            ->paginate(10)->each(function ($item){
                $item->can_reserve = $item->getReserveStatus(date('n月d日'))['can_reserve'];
            });
        return $lists;
    }

    /**获取预约详情
     * @param $param
     * @return WdXcxAppointmentContent|array|mixed|\think\Model|null
     */
    public function getAppointmentContentInfo($param)
    {
        $info = $this->appoint_model->getInfoById($param['id']);
        $info->appoint_lable = $info->appoint_lable ? explode(',', $info->appoint_lable) : [];
        $choose_date = $param['choose_date'] ? $param['choose_date'] : date('n月d日');
        $reserve_date = $this->getAppointDate($info, $choose_date);
        $info->reserve_date = $reserve_date['date'];
        $info->appoint_time_show = $info->getAppointTimeShow($reserve_date['appoint_data'], $choose_date);
        $info->can_reserve = $info->getReserveStatus(date('n月d日'))['can_reserve'];
        unset($info->appoint_time_info);
        $user = $this->getUserInfo();
        if($user){
            $info->user_integral = $user->integral;
        }else{
            $info->user_integral = 0;
        }
        return $info;
    }

    /**用户预约订单
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createUserAppointmentOrder($param, $user_id)
    {
        $info = $this->appoint_model->getInfoById($param['appoint_id']);
        $this->checkReserve($info, $param);
        $user = (new WdXcxUser())->getUserById($user_id);
        if($user->integral < $info->pay_score){
            throwError('用户积分不足');
        }
        $this->createUserAppointmentOrderHandler($user, $info, $param);
    }

    /**获取用户预约订单列表
     * @param $param
     * @param $user_id
     * @return \think\Paginator
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserAppointmentOrderLists($param, $user_id)
    {
        $type = $param['type'];
        if(!in_array($type, [0,1,2,3])){
            throwError('参数错误');
        }
        $lists = WdXcxUserAppointmentOrderLists::where('uniacid', $this->uniacid)
            ->where('user_id', $user_id)
            ->where(function ($query)use($type){
                if($type){
                    $query->where('status', $type);
                }
            })->order('id desc')
            ->withoutField('order_log, user_mobile, update_time, delete_time, pay_score, pay_price')
            ->paginate(10);
        return $lists;
    }

    /**获取订单核销二维码数据
     * @param $param
     * @param $user_id
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserAppointmentOrderQrcode($param, $user_id)
    {
        $order = WdXcxUserAppointmentOrderLists::where('user_id', $user_id)
            ->where('order_id', $param['order_id'])
            ->find();
        if(!$order){
            throwError('订单不存在');
        }
        if($order->status != 1){
            throwError('订单状态错误');
        }
        $qrcode = Utils::createQrcode($order->order_id, '', true);
        $appoint_date = str_replace('月', '/', rtrim($order->appoint_date, '日'));
        $pay_score = $order->pay_score;
        $appoint_time = $order->appoint_time;
        $appoint_title = $order->appoint_title;
        $user_mobile = $order->user_mobile;
        $appoint_thumb = $order->appoint_thumb;
        return compact('qrcode', 'pay_score', 'appoint_date', 'appoint_time', 'appoint_title', 'user_mobile', 'appoint_thumb');
    }

    /**创建用户预约订单数据
     * @param $user
     * @param $info
     * @param $param
     * @return void
     * @throws \cores\exception\BaseException
     */
    private function createUserAppointmentOrderHandler($user, $info, $param)
    {
        $order_id = generateOrderId('R');
        $appointment_order_model = new WdXcxUserAppointmentOrderLists();
        $appointment_order_data = [
            'uniacid' => $this->uniacid,
            'order_id' => $order_id,
            'user_id' => $user->id,
            'status' => 1,
            'pay_score' => $info->pay_score,
            'appoint_id' => $info->id,
            'appoint_title' => $info->appoint_title,
            'appoint_thumb' => $info->appoint_thumb,
            'appoint_date' => $param['appoint_date'],
            'appoint_time' =>$param['appoint_time'],
            'user_mobile' => $param['mobile'] ? $param['mobile'] : $user->mobile,
            'appoint_cate' => $info->cate_id,
            'appoint_start_time' => $this->getAppointTimeStamp($param)['start_time'],
            'appoint_end_time' => $this->getAppointTimeStamp($param)['end_time'],
            'order_log' => [
                [
                    'create_time' => time(),
                    'log' => '订单创建',
                ]
            ]
        ];
        $user_order_model = new WdXcxUserOrderLists();
        $user_order_data = [
            'uniacid' => $this->uniacid,
            'user_id' => $user->id,
            'order_id' => $order_id,
            'user_mobile' => $appointment_order_data['user_mobile'],
            'orderform_type' => WdXcxUserOrderLists::ORDER_TYPE_RESERVE,
            'status' => 1
        ];
        Db::startTrans();
        try {
            $appointment_order_model->save($appointment_order_data);
            $user_order_data['orderform_id'] = $appointment_order_model->id;
            $user_order_model->save($user_order_data);
            (new WdXcxUserIntegralRecord())->addRecord($user, [
                'order_id' => $order_id,
                'change_integral' => $info->pay_score,
                'message' => '积分预约',
                'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_RESERVE
            ]);
        }catch (Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
    }

    /**检查是否可预约
     * @param $info
     * @param $param
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function checkReserve($info, $param)
    {
        if($info->status != 1){
            throwError('预约已下架');
        }
        if(empty($param['appoint_date']) || empty($param['appoint_time'])){
            throwError('请选择预约日期与时间');
        }
        $appoint_date = $this->changeAppointDate($param['appoint_date']);
        if(strtotime($appoint_date) < strtotime(date('Y-m-d 00:00:00'))){
            throwError('预约日期已过期');
        }
        if(!in_array($param['appoint_time'], array_column($info->AppointTimeInfoArr, 'time_str'))){
            throwError('预约时间不存在');
        }
        if(WdXcxUserAppointmentOrderLists::where([
            'appoint_id' => $info->id,
            'appoint_date' => $param['appoint_date'],
            'appoint_time' => $param['appoint_time'],
        ])->whereIn('status', [1,2])->find()){
            throwError('该时间段已预约');
        }
        if($info->pay_score != $param['pay_score']){
            throwError('支付积分有误');
        }
    }

    private function changeAppointDate($appoint_date)
    {
        $appoint_date = str_replace('月', '-', $appoint_date);
        $appoint_date = str_replace('日', '', $appoint_date);
        return date('Y').'-'.$appoint_date;
    }

    private function getAppointTimeStamp($param)
    {
        $data = $this->changeAppointDate($param['appoint_date']);
        $appoint_time = explode('-', $param['appoint_time']);
        return [
            'start_time' => strtotime($data.' '.$appoint_time[0].':00'),
            'end_time' => strtotime($data.' '.$appoint_time[1].':00'),
        ];
    }


    /**获取预约日期
     * @param $info
     * @return array
     */
    private function getAppointDate($info, $choose_date=null)
    {
        $date = [];
        $appoint_data = [];
        for ($i = 0; $i < 7; $i++){
            $temp['date_str'] = date('n月d日', strtotime('+'.$i.' day'));
            $temp['week'] = $i == 0 ? '今日' : getWeek(date('w', strtotime('+'.$i.' day')));
            $reserve_info = $info->getReserveStatus($temp['date_str']);
            $temp['can_reserve'] = $reserve_info['can_reserve'];
            if($choose_date && $choose_date == $temp['date_str']){
                $appoint_data = $reserve_info['has_appoint'];
            }
            $date[] = $temp;
        }
        return compact('date', 'appoint_data');
    }

    /**核销预约订单
     * @param $order_id
     * @param $pay_price
     * @param $type
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkOutOrderInfo($order_id, $pay_price, $pay_type, $type=1)
    {
        $order_info = WdXcxUserAppointmentOrderLists::where([
            'uniacid' => $this->uniacid,
            'id' => $order_id,
        ])->find();
        if(!$order_info){
            throwError('订单不存在');
        }
        if($order_info->status != 1){
            throwError('订单状态不正确');
        }
        $user = (new WdXcxUser())->getUserById($order_info->user_id);
        if(!$user){
            throwError('用户不存在');
        }
        if($pay_type == 2){
            if($pay_price && bccomp($user->give_balance,  $pay_price, 2) != 1){
                throwError('用户零钱不足');
            }
        }else{
            $UserBalance = str_replace(',', '', $user->UserBalance);
            if($pay_price && bccomp($UserBalance, $pay_price, 2) != 1){
                throwError('用户余额不足');
            }
        }
        Db::startTrans();
        try {
            $order_info->status = 2;
            $order_info->pay_price = $pay_price;
            $order_info->order_log = array_merge($order_info->order_log, [
                [
                    'create_time' => time(),
                    'log' => $type == 1 ? '后台核销订单' : '扫码核销订单',
                ]
            ]);
            $order_info->save();
            WdXcxUserOrderLists::where('order_id', $order_info->order_id)->update(['status' => 2]);
            //退回积分
            if($order_info->pay_score){
                (new WdXcxUserIntegralRecord())->addRecord($user, [
                    'order_id' => $order_info->order_id,
                    'change_integral' => $order_info->pay_score,
                    'message' => '预约支付退回积分',
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_RESERVE_BACK
                ], WdXcxUserIntegralRecord::INTEGRAL_CHANGE_ADD);
            }
            if($pay_price){
                if($pay_type == 2){
                    //扣除零钱
                    (new WdXcxUserGiveBalanceRecord())->addRecord($user, [
                        'order_id' => $order_info->order_id,
                        'change_price' => $pay_price,
                        'message' => '预约支付订单',
                        'user_id' => $user->id,
                        'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_RESERVE_MONEY
                    ]);
                }else{
                    //扣除余额
                    (new WdXcxUserBalanceRecord())->addRecord($user, [
                        'order_id' => $order_info->order_id,
                        'change_price' => $pay_price,
                        'message' => '预约支付订单',
                        'user_id' => $user->id,
                        'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_RESERVE_MONEY
                    ]);
                }
            }
        }catch (Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
    }

    public function cancelOrder($order_id, $type)
    {
        $order_info = WdXcxUserAppointmentOrderLists::where([
            'uniacid' => $this->uniacid,
            'id' => $order_id,
        ])->find();
        if(!$order_info){
            throwError('订单不存在');
        }
        if($order_info->status != 1){
            throwError('订单状态不正确');
        }
        $user = (new WdXcxUser())->getUserById($order_info->user_id);
        if(!$user){
            throwError('用户不存在');
        }
        Db::startTrans();
        try {
            $order_info->status = 3;
            $order_log = $order_info->order_log;
            $order_info->order_log = array_merge($order_log, [
                [
                    'create_time' => time(),
                    'log' => $type == 1 ? '后台取消退积分' : '后台取消不退积分',
                ]
            ]);
            $order_info->save();
            if($type == 1 && $order_info->pay_score){
                (new WdXcxUserIntegralRecord())->addRecord($user, [
                    'order_id' => $order_info->order_id,
                    'change_integral' => $order_info->pay_score,
                    'message' => '后台取消预约订单退回积分',
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_RESERVE_BACK
                ], WdXcxUserIntegralRecord::INTEGRAL_CHANGE_ADD);
            }
        }catch (Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
    }

}