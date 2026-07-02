<?php

namespace app\common\service\pay;

use app\common\model\coupon\WdXcxUserCoupon;
use app\common\model\order\WdXcxUserBuyGradeOrderLists;
use app\common\model\order\WdXcxUserCateringOrderLists;
use app\common\model\order\WdXcxUserGamecoinOrderLists;
use app\common\model\order\WdXcxUserOrderLists;
use app\common\model\order\WdXcxUserRechargeOrderLists;
use app\common\model\order\WdXcxUserTicketOrderLists;
use app\common\model\user\WdXcxRechargePackage;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserBalanceRecord;
use app\common\model\user\WdXcxUserGiveBalanceRecord;
use app\common\model\user\WdXcxUserIntegralRecord;
use app\common\model\user\WdXcxUserWxpayRecord;
use app\common\model\user\WdXcxVipgrade;
use app\common\service\catering\CateringService;
use app\common\service\distribution\DistributionService;
use app\common\service\gamecoin\GamecoinService;
use app\common\service\ticket\TicketingService;
use app\common\service\user\UserService;
use app\common\service\WxService;
use app\common\service\ylb\YlbApiService;
use think\cache\driver\Redis;
use think\Exception;
use think\facade\Db;
use think\facade\Log;

class PayNotifyService
{
    public function notify()
    {
        $pay_app =(new WxService(2))->getPayCallBackInfo();
        $result = $pay_app->handlePaidNotify(function($notify, $fail){
            $this->orderChange($notify);
        });
        $result->send();
    }

    /**校验通知
     * @param $param
     * @return bool
     */
    private function verifyNotify($param)
    {
//        return true;
        if(empty($param['extend'])){
            return false;
        }
        $new_extend = strtoupper(md5(substr($param['buyerId'], 0, 10).substr($param['ordNo'], 0, 10)));
        return $new_extend == $param['extend'];
    }

    /**改变订单状态
     * @param $param
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function orderChange($param)
    {
        $order_id_type = substr($param['out_trade_no'], 0, 1);
        switch ($order_id_type){
            case 'V': //购买会员
//                $this->changeRechargeOrder($param);
                $this->changeBuyGradeOrder($param);
                break;
            case 'F':
                $this->changeCateringOrder($param);
                break;
            case 'G':
                $this->changeGamecoinOrder($param);
                break;
            case 'T':
                $this->changeTicketingOrder($param);
                break;
            default:
                Log::info('订单类型错误:'.$param['ordNo']);
                break;
        }

    }

    /**处理套票订单微信支付回调
     * @param $param
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeTicketingOrder($param)
    {
        $redis = new Redis(GetRedisConf());
        $order_data = $redis->get('ticketing_order_'.$param['ordNo']);
        if($order_data){
            $order_data = json_decode($order_data, true);
            $has = WdXcxUserTicketOrderLists::where('order_id', $param['ordNo'])->find();
            if($has){
                Log::info('订单状态不正确或已支付:'.$param['ordNo']);
            }else{
                (new TicketingService(app()))->saveTicketOrderNotify($order_data);
                $redis->delete('ticketing_order_'.$param['ordNo']);
            }
        }else{
            Log::info('套票微信支付订单不存在:'.$param['ordNo']);
        }
    }

    /**游戏币购买订单微信支付回调
     * @param $param
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function changeGamecoinOrder($param)
    {
        $redis = new Redis(GetRedisConf());
        $order_data = $redis->get('gamecoin_order_'.$param['ordNo']);
        if($order_data){
            $order_data = json_decode($order_data, true);
            $has = WdXcxUserGamecoinOrderLists::where('order_id', $param['ordNo'])->find();
            if($has){
                Log::info('订单状态不正确或已支付:'.$param['ordNo']);
            }else{
                (new GamecoinService(app()))->saveGamecoinOrder($order_data);
                $redis->delete('gamecoin_order_'.$param['ordNo']);
            }
        }else{
            Log::info('游戏币微信支付订单不存在:'.$param['ordNo']);
        }
    }

    /**点餐订单回调通知
     * @param $param
     * @return void
     * @throws \cores\exception\BaseException
     */
    private function changeCateringOrder($param)
    {
        $redis = new Redis(GetRedisConf());
        $order_data = $redis->get('catering_order_'.$param['ordNo']);
        if($order_data){
            $order_data = json_decode($order_data, true);
            $has = WdXcxUserCateringOrderLists::where('order_id', $param['ordNo'])->find();
            if($has){
                Log::info('订单状态不正确或已支付:'.$param['ordNo']);
            }else{
                (new CateringService(app()))->saveCateringOrder($param['ordNo'], null, $order_data, 1);
                $redis->delete('catering_order_'.$param['ordNo']);
            }
        }else{
            Log::info('点餐微信支付订单不存在:'.$param['ordNo']);
        }
    }

    private function changeBuyGradeOrder($param)
    {
        $order = WdXcxUserBuyGradeOrderLists::where('order_id', $param['out_trade_no'])
            ->where('status', 1)
            ->find();
        if(!$order){
            Log::info('会员购买订单不存在:'.$param['out_trade_no']);
            return;
        }
        Db::startTrans();
        try {
            $order->status = 2;
            $order->save();
            //保存用户微信消费记录
            WdXcxUserWxpayRecord::create([
                'uniacid' => 1,
                'user_id' => $order->user_id,
                'order_id' => $order->order_id,
                'pay_price' => $order->pay_price,
                'create_time' => time(),
                'order_type' => WdXcxUserWxpayRecord::WX_PAY_BUY_GRADE_ORDER
            ]);
            //改变用户会员等级
            $user = WdXcxUser::where('id', $order->user_id)->find();
            $user->vip_grade = $order->grade_level;
            $user->save();
            $grade_info = WdXcxVipgrade::where('grade_level', $order->grade_level)->find();
            $limit_day = $order->buy_day_limit == 1 ? 365 : ($order->buy_day_limit == 2 ? 90 : 30);
            $end_time = strtotime(date('Y-m-d', time()) . ' 23:59:59') + $limit_day * 86400;
            $user->changeUserVipGrade($user, $order->grade_level, $end_time, '购买会员等级【'.$grade_info->grade_name.'】');
        }catch (Exception $e){
            Db::rollback();
            Log::info('会员购买订单修改失败:'.$param['out_trade_no']);
        }
        Db::commit();
    }


    /**修改充值订单
     * @param $param
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function changeRechargeOrder($param)
    {
        $recharge_order = WdXcxUserRechargeOrderLists::where('order_id', $param['ordNo'])
            ->where('status', 1)
            ->find();
//        if($recharge_order && bccomp($recharge_order->pay_price, $param['amt'], 2) === 0){
            Db::startTrans();
            try {
                $user = WdXcxUser::find($recharge_order->user_id);
                $recharge_package = WdXcxRechargePackage::where('id', $recharge_order->recharge_id)->find();
                if(!$recharge_package){
                    throw new Exception('充值套餐不存在');
                }
                //处理赠送积分
                if($recharge_package->getscore > 0){
                    $recharge_order->getscore = $recharge_package->getscore;
                    (new WdXcxUserIntegralRecord())->addRecord($user, [
                        'order_id' => $recharge_order->order_id,
                        'change_integral' => $recharge_package->getscore,
                        'message' => '充值送积分',
                        'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_RECHARGE_SEND
                    ], WdXcxUserIntegralRecord::INTEGRAL_CHANGE_ADD);
                }
                //处理卡券
                $coupon_con = [];
                if($recharge_package->coupon_con){
                    $coupon_con = (new WdXcxUserCoupon())->giveUserCoupon($user, $recharge_package->coupon_con, '充值赠送', WdXcxUserCoupon::USER_COUPON_RECHARGE_SEND);
                }
                //处理用户余额
                $change_price = $recharge_package->money;
                (new WdXcxUserBalanceRecord())->addRecord($user, [
                    'order_id' => $recharge_order->order_id,
                    'change_price' => $change_price,
                    'message' => '充值',
                    'user_id' => $user->id,
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_RECHARGE
                ], WdXcxUserBalanceRecord::BALANCE_CHANGE_ADD);
                if($recharge_package->getmoney > 0){
//                    $recharge_order->give_price = $recharge_package->getmoney;
//                    (new WdXcxUserBalanceRecord())->addRecord($user, [
//                        'order_id' => $recharge_order->order_id.'-1',
//                        'change_price' => $recharge_package->getmoney,
//                        'message' => '充值送金额',
//                        'user_id' => $user->id,
//                        'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_RECHARGE_SEND
//                    ], WdXcxUserBalanceRecord::BALANCE_CHANGE_ADD);
                    (new WdXcxUserGiveBalanceRecord())->addRecord($user, [
                        'order_id' => $recharge_order->order_id,
                        'change_price' => $recharge_package->getmoney,
                        'message' => '充值送零钱',
                        'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_RECHARGE_SEND,
                    ], WdXcxUserGiveBalanceRecord::BALANCE_CHANGE_ADD);
                }
                //处理充值增加游戏币
                if($recharge_package->get_gamecoin > 0){
                    (new YlbApiService())->changeUserValues($user->leaguer_id, 402, $recharge_package->get_gamecoin, '充值赠送游戏币');
                    $recharge_order->get_gamecoin = $recharge_package->get_gamecoin;
                }
                //更新订单状态
                $recharge_order->status = 2;
                $recharge_order->coupon_con = $coupon_con;
                $recharge_order->pay_info = $param;
                $recharge_order->save();
                WdXcxUserOrderLists::create([
                    'uniacid' => $recharge_order->uniacid,
                    'user_id' => $recharge_order->user_id,
                    'user_mobile' => $user->mobile,
                    'order_id' => $recharge_order->order_id,
                    'orderform_id' => $recharge_order->id,
                    'orderform_type' => WdXcxUserOrderLists::ORDER_TYPE_RECHARGE,
                    'status' => 2,
                ]);
                $user->total_recharge = bcadd($user->total_recharge, $recharge_package->money, 2);
                $user->save();
                //处理充值送会员
                (new UserService(app()))->checkUserRechargeVipGrade($user->id, $recharge_order->order_id);
                //充值分销订单
                (new DistributionService(app()))->createFxOrder($user, $recharge_order->order_id, $recharge_order->id, WdXcxUserOrderLists::ORDER_TYPE_RECHARGE, $recharge_package->money);
            }catch (\Exception $exception){
                Db::rollback();
                Log::info('订单回调处理异常:'.$exception->getMessage());
            }
            Db::commit();
//        }else{
//            Log::info('订单不存在:'.$param['ordNo']);
//        }
    }
}