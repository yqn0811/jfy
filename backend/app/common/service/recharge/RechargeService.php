<?php

namespace app\common\service\recharge;

use app\common\model\order\WdXcxUserExchangeOrderLists;
use app\common\model\order\WdXcxUserRechargeOrderLists;
use app\common\model\order\WdXcxUserRechargeTempOrder;
use app\common\model\user\WdXcxRechargePackage;
use app\common\service\BaseService;
use app\common\service\pay\PayService;
use think\App;

class RechargeService extends BaseService
{
    private $recharge_model;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->recharge_model = new WdXcxRechargePackage();
    }

    /**充值套餐列表
     * @return WdXcxRechargePackage[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRechargeList()
    {
        $lists = $this->recharge_model
            ->where('uniacid', $this->uniacid)
            ->withoutField('create_time, update_time, delete_time')
            ->order('id desc')
            ->select()->each(function ($item){
                $item->coupon_show = $item->CouponConShow;
                $item->recharge_send = $item->RechargeSend;
                unset($item->coupon_con);
            });
        return $lists;
    }

    /**创建支付订单获取支付信息
     * @param $param
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createRechargeOrder($param)
    {
        $info = $this->recharge_model->getRechargeInfoById($param['rid']);
        if(bccomp($info->money, $param['pay_price'], 2) !== 0){
            throwError('充值金额错误');
        }
        $order_id = generateOrderId('C');
        $pay_info = (new PayService($this->app))->getPayInfo([
            'order_id' => $order_id,
            'pay_price' => $info->money,
            'subject' => '充值订单',
            'openid' => request()->userOpenid(),
        ]);
        $recharge_order = [
            'uniacid' => $this->uniacid,
            'user_id' => request()->userID(),
            'order_id' => $order_id,
            'pay_price' => $info->money,
            'recharge_id' => $param['rid'],
            'status' => 1,
            'invite_code' => empty($param['invite_code']) ? '' : $param['invite_code'],
            'pay_info' => [
                'timeStamp' => $pay_info['payTimeStamp'],
                'nonceStr' => $pay_info['paynonceStr'],
                'package' => $pay_info['payPackage'],
                'signType' => $pay_info['paySignType'],
                'paySign' => $pay_info['paySign'],
            ],
        ];
        //存入充值订单表
        WdXcxUserRechargeOrderLists::create($recharge_order);
        return [
            'order_id' => $order_id,
            'pay_info' => $recharge_order['pay_info']
        ];
    }

    /**查询订单状态
     * @param $order_id
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function checkPayStatus($order_id, $user_id)
    {
        if(!$order_id){
            throwError('参数不完整');
        }
        $order = WdXcxUserRechargeOrderLists::where('user_id', $user_id)
            ->where('order_id', $order_id)
            ->find();
        if(!$order){
            throwError('订单不存在');
        }
        if($order->status != 2){
            throwError('支付中');
        }
    }
}