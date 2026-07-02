<?php

namespace app\common\service\distribution;

use app\common\model\distribution\WdXcxDistributionBase;
use app\common\model\distribution\WdXcxDistributionOrderLists;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserCommissionRecord;
use app\common\model\user\WdXcxUserWithdrawalRecord;
use app\common\service\BaseService;
use app\common\service\pay\WxTransferMoneyService;
use app\common\service\WxService;
use think\App;
use think\facade\Db;
use think\facade\Log;

class DistributionService extends BaseService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**分销基础信息保存
     * @param $param
     * @return void
     */
    public function saveBase($param)
    {
        $base = $this->getBase();
        if(!$base){
            $base = new WdXcxDistributionBase();
            $base->uniacid = $this->uniacid;
        }
        $base->status = isset($param['status']) ? $param['status'] : 0;
        $base->scan_get = isset($param['scan_get']) ? $param['scan_get'] : 1;
        $base->lock_day = isset($param['lock_day']) ? ($param['lock_day'] < 0 ? 30 : $param['lock_day']) : 30;
        $base->commission_bill = isset($param['commission_bill']) ? ($param['commission_bill'] < 0 ? 0 : $param['commission_bill']) : 0;
        $base->charge_bill = isset($param['charge_bill']) ? ($param['charge_bill'] < 0 ? 0 : $param['charge_bill']) : 0;
        $base->withdrawal_rise = isset($param['withdrawal_rise']) ? ($param['withdrawal_rise'] < 0 ? 0 : $param['withdrawal_rise']) : 0;
        $base->commission_settle = isset($param['commission_settle']) ? ($param['commission_settle'] < 0 ? 0 : $param['commission_settle']) : 0;
        $base->share_thumb = isset($param['commonuploadpic1']) ? $param['commonuploadpic1'] : '';
        $coupon_id = isset($param['coupon_id']) ? $param['coupon_id'] : [];
        $coupon_num = isset($param['coupon_num']) ? $param['coupon_num'] : [];
        $coupon_info = [];
        if(count($coupon_id) > 0 && count($coupon_num) > 0){
            foreach ($coupon_id as $key => $value){
                if(!empty($coupon_num[$key])){
                    $coupon_info[] = [
                        'coupon_id' => $value,
                        'coupon_num' => $coupon_num[$key]
                    ];
                }
            }
        }
        $base->coupon_info = $coupon_info;
        $base->save();
    }

    /**获取分销基础信息
     * @return WdXcxDistributionBase|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBase()
    {
        return WdXcxDistributionBase::where('uniacid', $this->uniacid)->find();
    }

    /**分销订单列表
     * @param $param
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function getDistributionLists($param)
    {
        $size = empty($param['size']) ? 10 : $param['size'];
        $suids = [];
        if(!empty($param['key'])){
            $suids = (new WdXcxUser())->searchUserByKey($param['key']);
        }

        $lists = WdXcxDistributionOrderLists::where('uniacid', $this->uniacid)
            ->where(function ($query)use($param, $suids){
                if(!empty($param['key'])){
                    $query->where(function ($query)use($param, $suids){
                        $query->whereIn('user_id', $suids)->whereOr('parent_id', $suids)->whereOr('order_id', 'like', '%'.$param['key'].'%');
                    });
                }
                if(!empty($param['distribution_id'])){
                    $query->where('parent_id', $param['distribution_id']);
                }
                if(!empty($param['status']) && $param['status'] != 100){
                    $query->where('status', $param['status']);
                }
                if(!empty($param['orderform_type'])){
                    $query->where('orderform_type', $param['orderform_type']);
                }
                if(!empty($param['start_get'])){
                    $query->where('create_time', '>=', strtotime($param['start_get']));
                }
                if(!empty($param['end_get'])){
                    $query->where('create_time', '<=', strtotime($param['end_get']));
                }
            })
            ->order('id desc')
            ->paginate([
                'list_rows' => $size,
                'query' => request()->param()
            ]);
        $total_money = WdXcxDistributionOrderLists::where('uniacid', $this->uniacid)
            ->where(function ($query)use($param, $suids){
                if(!empty($param['key'])){
                    $query->where(function ($query)use($param, $suids){
                        $query->whereIn('user_id', $suids)->whereOr('parent_id', $suids)->whereOr('order_id', 'like', '%'.$param['key'].'%');
                    });
                }
                if(!empty($param['distribution_id'])){
                    $query->where('parent_id', $param['distribution_id']);
                }
                if(!empty($param['status']) && $param['status'] != 100){
                    $query->where('status', $param['status']);
                }
                if(!empty($param['orderform_type'])){
                    $query->where('orderform_type', $param['orderform_type']);
                }
                if(!empty($param['start_get'])){
                    $query->where('create_time', '>=', strtotime($param['start_get']));
                }
                if(!empty($param['end_get'])){
                    $query->where('create_time', '<=', strtotime($param['end_get']));
                }
            })->sum('commission_money');
        return [$lists, $total_money];
    }

    /**提现申请列表
     * @param $param
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getUserWithdrawalLists($param)
    {
        $suids = [];
        if(!empty($param['key'])){
            $suids = (new WdXcxUser())->searchUserByKey($param['key']);
        }
        $lists = WdXcxUserWithdrawalRecord::where('uniacid', $this->uniacid)
            ->where(function ($query)use($param, $suids){
                if(isset($param['status']) && $param['status'] != 100){
                    $query->where('status', $param['status']);
                }
                if(!empty($param['key'])){
                    $query->where(function ($query)use($param, $suids){
                        $query->whereIn('user_id', $suids)->whereOr('true_name', 'like', '%'.$param['key'].'%');
                    });
                }
                if(!empty($param['user_id'])){
                    $query->where('user_id', $param['user_id']);
                }
                if(!empty($param['start_get'])){
                    $query->where('create_time', '>=', strtotime($param['start_get']));
                }
                if(!empty($param['end_get'])){
                    $query->where('create_time', '<=', strtotime($param['end_get']));
                }
            })
//            ->orderRaw("find_in_set(status, '0,1,-1')")
            ->order('id desc')
            ->paginate([
            'list_rows' => 10,
            'query' => request()->param()
        ]);
        return $lists;
    }

    /**审核提现申请
     * @param $param
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkWithdrawalStatus($param)
    {
        if(empty($param['id']) || empty($param['operate'])){
            $this->error('参数不完整');
        }
        $record = WdXcxUserWithdrawalRecord::where('id', $param['id'])->find();
        if(!$record){
            $this->error('记录不存在');
        }
        if($record->status != 0){
            $this->error('状态不正确');
        }
        if($param['operate'] == 100){ //线上
            $order_id = substr(generateOrderId('WP'), 0, 18);
            $openid = WdXcxUser::where('id', $record->user_id)->value('openid');
            $pay_service = new WxTransferMoneyService();
            $pay_param = [
                'order_sn' => $order_id,
                'openid' => $openid,
                'title' => '佣金提现',
                'remark' => '佣金提现',
                'money' => $record->transfer_money,
                'user_name' => input('true_name') ?? '',//超过2000需提供姓名，暂时不控制
            ];
            $pay_service->setParams($pay_param);
            $result = $pay_service->transfer;
            if (!$result) {
                throwError($pay_service->getErrorMessage());
            }
            $record->status = 1;
            $msg = '线上已打款';
        }elseif ($param['operate'] == 200){
            $record->status = 1;
            $msg = '线下已打款';
        }else{
            $record->status = -1;
            $msg = '审核拒绝';
        }
        $record->withdrawal_log = array_merge($record->withdrawal_log, [
            [
                'log' => $msg,
                'time' => time(),
            ]
        ]);
        Db::startTrans();
        try {
            $record->save();
            if($record->status == -1){ //审核拒绝 退回佣金
                (new WdXcxUserCommissionRecord())->addRecord($record->user_id, [
                    'change_price' => $record->total_money,
                    'order_id' => '',
                    'message' => '审核拒绝退回',
                    'change_source' => WdXcxUserCommissionRecord::CHANGE_SOURCE_REFUSE_WITHDRAWAL
                ], WdXcxUserCommissionRecord::COMMISSION_CHANGE_ADD);
            }
        }catch (\Exception $e){
            Db::rollback();
            throwError($e->getMessage());
        }
        Db::commit();
    }


    /**创建分销订单
     * @param $user
     * @param $order_id
     * @param $order_sn
     * @param $order_type
     * @param $order_price
     * @param $status
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createFxOrder($user, $order_id, $order_sn, $order_type, $order_price, $status=1)
    {
        $has = WdXcxDistributionOrderLists::where([
            'user_id' => $user->id,
            'order_id' => $order_id
        ])->find();
        if($has){
            Log::info('分销订单已存在');
            return;
        }
        $distribution_base = $this->getBase();
        if($distribution_base && $distribution_base->status == 1){
            $distribution = $user->getUserDistributionInfo();
            if($distribution){
                try {
                    $distribution_user = (new WdXcxUser())->getUserById($distribution->parent_id);
                    if($distribution_user->distribution_status == 1){
                        if($distribution_base->commission_bill > 0){
                            $distribution_order = new WdXcxDistributionOrderLists();
                            $distribution_order->uniacid = $this->uniacid;
                            $distribution_order->user_id = $user->id;
                            $distribution_order->parent_id = $distribution_user->id;
                            $distribution_order->order_id = $order_id;
                            $distribution_order->orderform_id = $order_sn;
                            $distribution_order->orderform_type = $order_type;
                            $distribution_order->order_money = $order_price;
                            $distribution_order->commission_money = bcmul($order_price, bcdiv($distribution_base->commission_bill, 100, 4), 2);
                            $distribution_order->status = $status;
                            $distribution_order->settle_time = $status == 1 ? time() + 86400 * $distribution_base->commission_settle : 0;
                            $log = $status == 1 ? '创建完成订单，待结算' : '创建订单，待使用';
                            $distribution_order->order_log = [
                                [
                                    'time' => time(),
                                    'log' => $log.', 佣金比例：'.$distribution_base->commission_bill,
                                ]
                            ];
                            $distribution_order->save();
                        }else{
                            Log::info('佣金比例为0，无需分佣');
                        }
                    }else{
                        Log::info('绑定上级已禁用');
                    }
                }catch (\Exception $e){
                    Log::info('绑定上级不存在');
                }
            }else{
                Log::info('用户没有绑定上级');
            }
        }else{
            Log::info('分销功能未开启');
        }

    }

    /**修改订单状态
     * @param $order_id
     * @param $status
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeFxOrderStatus($order_id, $status)
    {
        $order = WdXcxDistributionOrderLists::where('order_id', $order_id)->where('status', 0)->find();
        if($order){
            $distribution_base = $this->getBase();
            $order->status = $status;
            if($status == 1){
                $order->settle_time = time() + 86400 * $distribution_base->commission_settle;
                $log = '订单完成，待结算';
            }else{
                $log = '订单取消，已取消';
            }
            $order->order_log = array_merge($order->order_log, [
                'time' => time(),
                'log' => $log,
            ]);
            $order->save();
        }
    }

    /***************************************************** API *******************************************************************/

    /**分销首页获取用户分销统计数据
     * @param $param
     * @param $user_id
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserDistributionData($param, $user_id)
    {
        $user = (new WdXcxUser())->getUserById($user_id);
        $user->checkFxStatus();
        $date_type = isset($param['date_type']) ? $param['date_type'] : 1;
        $time_data = $this->getStartAndEndTime($date_type, $param['date_start'], $param['date_end']);
        $result = (new WdXcxDistributionOrderLists())->getUserDistributionData($user_id, $date_type, $time_data);
        $result['total_commission'] = $user->total_commission;
        $result['current_commission'] = $user->current_commission;
        if($date_type == 1){
            $result['date_time'] = date('Y/m/d');
        }elseif($date_type == 2){
            $result['date_time'] = date('Y/m/d', strtotime('-1 day'));
        }else{
            $result['date_time'] = date('Y/m/d', $time_data[0]) . '~' . date('Y/m/d', $time_data[1]);
        }
        return $result;
    }

    /**获取分销商下级用户列表
     * @param $user_id
     * @return \think\Paginator
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserDistributionJunior($user_id)
    {
        $user = (new WdXcxUser())->getUserById($user_id);
        $user->checkFxStatus();
        $lists = $user->sonDistribution()
            ->field('user_id, parent_id, lock_time')
            ->order('update_time desc')
            ->paginate(15)->each(function($item){
                $item->lock_time = date('Y.m.d', $item->lock_time);
                $item->get_commission = $item->user->contributeOrder()->sum('commission_money');
                $item->user_name = $item->user->nickname;
                $item->user_avatar = $item->user->avatar;
                unset($item->user);
            });
        return $lists;
    }

    /**获取分销订单列表
     * @param $param
     * @param $user_id
     * @return \think\Paginator
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserDistributionOrderLists($param, $user_id)
    {
        $user = (new WdXcxUser())->getUserById($user_id);
        $user->checkFxStatus();
        $lists = $user->orderDistribution()
            ->where(function ($query)use($param){
                if(!empty($param['status'])){
                    if($param['status'] == 1){
                        $query->whereIn('status', [0,1]);
                    }elseif ($param['status'] == 2){
                        $query->where('status', 2);
                    }elseif ($param['status'] == 3){
                        $query->where('status', -1);
                    }
                }
            })->order('create_time desc')
            ->paginate(10)->each(function($item){
                $item->order_data = $item->getOrderformData;
                $item->order_data->user_info = $item->order_data->UserInfo;
                unset($item->getOrderformData, $item->order_log, $item->update_time, $item->delete_time, $item->id);
            });
        return $lists;
    }

    /**获取分销提现基础信息
     * @param $user_id
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserDistributionWithdrawalInfo($user_id)
    {
        $user = (new WdXcxUser())->getUserById($user_id);
        $user->checkFxStatus();
        $base = $this->getBase();
        $old_true_name = WdXcxUserWithdrawalRecord::where('user_id', $user_id)
            ->where('true_name', '<>', '')
            ->order('id desc')
            ->limit(1)
            ->value('true_name');
        return [
            'user_commission' => $user->current_commission,
            'charge_bill' => $base->charge_bill == 0 ? 0 : bcdiv($base->charge_bill, 100, 4),
            'withdrawal_rise' => $base->withdrawal_rise,
            'true_name' => $old_true_name ? $old_true_name : '',
            'max_rise' => '500.00',
            'note_prompt' => '1. 满足条件即可提现；
2. 提现金额在审核通过后直接到账微信零钱；
3. 所有解释权归4FUN所有'
        ];
    }

    /**提现记录列表
     * @param $user_id
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getUserDistributionWithdrawalLists($user_id)
    {
        return (new DistributionService(app()))->getUserWithdrawalLists(['user_id' => $user_id]);
    }

    /**用户提现申请
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserDistributionSubmitWithdrawal($param, $user_id)
    {
        if(empty($param['total_money'])){
            throwError('请输入提现金额');
        }
        if(bccomp($param['total_money'], 2000, 2) >= 0 && empty($param['true_name'])){
            throwError('提现大于2000元，请输入真实姓名');
        }
        $user = (new WdXcxUser())->getUserById($user_id);
        $user->checkFxStatus();
        if(bccomp($user->current_commission, $param['total_money'], 2) < 0){
            throwError('用户可提现金额不足');
        }
        $base = $this->getBase();
        if(bccomp($param['total_money'], $base->withdrawal_rise, 2) < 0){
            throwError('提现金额低于最低提现金额');
        }
        $record = new WdXcxUserWithdrawalRecord();
        $record->uniacid = $user->uniacid;
        $record->user_id = $user_id;
        $record->total_money = $param['total_money'];
        $record->charge_money = $base->charge_bill > 0 ? bcmul($param['total_money'], bcdiv($base->charge_bill, 100, 4), 2) : 0;
        $record->transfer_money = bcsub($param['total_money'], $record->charge_money, 2);
        $record->true_name = $param['true_name'];
        $record->status = 0;
        $record->withdrawal_log = [
            [
                'time' => time(),
                'log' => '提交提现申请,手续费比例:'.$base->charge_bill.'%'
            ]
        ];
        Db::startTrans();
        try {
            $record->save();
            (new WdXcxUserCommissionRecord())->addRecord($user->id, [
                'change_price' => $param['total_money'],
                'order_id' => '',
                'message' => '用户提现',
                'change_source' => WdXcxUserCommissionRecord::CHANGE_SOURCE_WITHDRAWAL
            ]);
        }catch (\Exception $e){
            Db::rollback();
            throwError($e->getMessage());
        }
        Db::commit();
    }

    /**获取不限制的分销二维码
     * @param $user_id
     * @return string|null
     * @throws \cores\exception\BaseException
     */
    public function getUserDistributionQrcode($user_id)
    {
        $file_path = public_path().'image/ewm';
        $file_name = 'fx_share_'.$user_id.'.jpg';
        (new WxService())->getUnlimitQrcode([
            'scene' => 'did='.$user_id,
            'path' => 'pages/index/index',
            'filename' => $file_name,
            'filepath' => $file_path,
        ]);
        $qrcode = getLocalImage('/image/ewm/'.$file_name);
        $tips = '长按识别二维码进入小程序';
        $nickname = (new WdXcxUser())->getUserById($user_id)->nickname;
        $show_name = mb_substr($nickname, 0, 9).'向您推荐';
        $share_thumb = $this->getBase()->share_thumb;
        return compact('user_id', 'tips', 'show_name', 'share_thumb', 'qrcode');
    }


}