<?php

namespace app\common\service\ticket;

use app\common\model\coupon\WdXcxUserCoupon;
use app\common\model\order\WdXcxUserOrderLists;
use app\common\model\order\WdXcxUserTicketOrderLists;
use app\common\model\ticket\WdXcxTicketGoodsInfo;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserBalanceRecord;
use app\common\model\user\WdXcxUserGiveBalanceRecord;
use app\common\model\user\WdXcxUserWxpayRecord;
use app\common\service\BaseService;
use app\common\service\distribution\DistributionService;
use app\common\service\pay\PayService;
use app\common\service\user\UserService;
use app\common\service\ylb\YlbApiService;
use think\App;
use think\cache\driver\Redis;
use think\facade\Config;
use think\facade\Db;
use think\facade\Log;

class TicketingService extends BaseService
{
    private $ticket_model;
    private $ylb_service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->ticket_model = new WdXcxTicketGoodsInfo();
        $this->ylb_service = new YlbApiService();
    }

    /**获取门票列表
     * @param $param
     * @return array
     */
    public function getTicketLists()
    {
        $lists = $this->ylb_service->getAllShopGoodsLists();
        $result = [];
        foreach ($lists as $item){
            $info = $this->ticket_model->getInfoByGoodsId($item['GoodsID'], true);
            if($info && $info->status == 1){
                $result[] = [
                    'goods_id' => $info->id,
                    'goods_name' => $item['GoodsName'],
                    'goods_price' => $item['GoodsPrice'],
                    'goods_thumb' => empty($info->ticket_thumb) ? getLocalImage(Config::get('ylb.default_data.ticket_thumb')) : $info->ticket_thumb,
                ];
            }

        }
        return $result;
    }

    /**获取套票详情
     * @param $param
     * @return array
     * @throws \cores\exception\BaseException
     */
    public function getTicketInfo($param)
    {
        if(empty($param['goods_id'])){
            throwError('参数不完整');
        }
        $goods_id = $param['goods_id'];
        $goods_info = $this->ticket_model->getInfoById($goods_id);
        if(!$goods_info){
            throwError('指定详情不存在');
        }
        $info = $this->ylb_service->getAllShopGoodsLists($goods_info->goods_id);
        if(empty($info)){
            throwError('指定详情不存在');
        }
        $info = $info[0];
        $result = [
            'goods_id' => $goods_id,
            'goods_name' => $info['GoodsName'],
            'goods_price' => $info['GoodsPrice'],
            'goods_thumb' => empty($goods_info->ticket_thumb) ? getLocalImage(Config::get('ylb.default_data.ticket_thumb')) : $goods_info->ticket_thumb,
            'ticket_lable' => empty($goods_info->ticket_lable) ? [] : $goods_info->TicketLableArr,
            'vip_discount' => empty($goods_info->vip_discount) ? 0 : $goods_info->vip_discount,
            'ticket_descs' => empty($goods_info->ticket_descs) ? '' : $goods_info->ticket_descs,
            'address_info' => Config::get('comm_config.address'),
            'discount_price' => 0,
            'pay_price' => 0,
            'user_blance' => 0,
            'give_balance' => 0,
        ];
        $user = $this->getUserInfo();
        if($user){
            $result['pay_price'] = $user->getUserDiscountPrice($user, $info['GoodsPrice'])['discount_price'];
            $result['discount_price'] = bcsub($info['GoodsPrice'], $result['pay_price'], 2);
            $result['user_blance'] = str_replace(',', '', $user->UserBalance);
            $result['give_balance'] = $user->give_balance;
        }
        return $result;
    }

    /**创建套票订单
     * @param $param
     * @return array|null
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createTicketOrder($param)
    {
        if(empty($param['mobile'])){
            $param['mobile'] = request()->userMobile();
        }else{
            if(!validPhoneNumber($param['mobile'])){
                throwError('手机号格式错误');
            }
        }
        $info = $this->ticket_model->getInfoById($param['goods_id']);
        if(!$info){
            throwError('指定商品不存在或已下架');
        }
        $goods_info = $this->getGoodsInfoFromYlb($info->goods_id);
        $pay_price_info = $this->checkByGoods($param, $info, $goods_info);
        return $this->createTicketPayOrder($pay_price_info, $param, $info, $goods_info);
    }

    /**写入订单记录
     * @param $pay_price_info
     * @param $param
     * @param $info
     * @param $goods_info
     * @return array|void
     * @throws \cores\exception\BaseException
     */
    private function createTicketPayOrder($pay_price_info, $param, $info, $goods_info)
    {
        $order_id = generateOrderId('T');
        $pay_type = isset($param['pay_type']) ? $param['pay_type'] : 0;
        $user = $pay_price_info['user'];
        if($pay_type == 1){
            $pay_info = (new PayService($this->app))->getPayInfo([
                'order_id' => $order_id,
                'pay_price' => $pay_price_info['need_pay_price'],
                'subject' => '购买游戏币订单',
                'openid' => $user['openid'],
            ]);
            $redis = new Redis(GetRedisConf());
            $redis->set('ticketing_order_'.$order_id, json_encode(compact('pay_price_info', 'order_id', 'param', 'goods_info', 'info'), JSON_UNESCAPED_UNICODE), 600);
            return [
                'timeStamp' => $pay_info['payTimeStamp'],
                'nonceStr' => $pay_info['paynonceStr'],
                'package' => $pay_info['payPackage'],
                'signType' => $pay_info['paySignType'],
                'paySign' => $pay_info['paySign'],
                'pay_type' => $pay_type,
                'order_id' => $order_id,
            ];
        }else{
            $this->saveTicketOrderData($order_id, $param, $goods_info, $info, $pay_price_info, $pay_type);
            return ['msg' => '下单成功', 'pay_type' => $pay_type, 'order_id' => $order_id];
        }
    }

    /**套票订单微信支付回调处理
     * @param $notify_data
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function saveTicketOrderNotify($notify_data)
    {
        $order_id = $notify_data['order_id'];
        $param = $notify_data['param'];
        $goods_info = $notify_data['goods_info'];
        $info = $notify_data['info'];
        $pay_price_info = $notify_data['pay_price_info'];
        $this->saveTicketOrderData($order_id, $param, $goods_info, $info, $pay_price_info, 1);
    }

    /**保存订单数据
     * @param $order_id
     * @param $param
     * @param $goods_info
     * @param $info
     * @param $pay_price_info
     * @param $pay_type
     * @return void
     * @throws \cores\exception\BaseException
     */
    private function saveTicketOrderData($order_id, $param, $goods_info, $info, $pay_price_info, $pay_type)
    {
        $user = $pay_price_info['user'];
        if($goods_info['GoodsType'] == 6){ //组合商品
            //创建订单
            $this->ylb_service->createOrderNew($user['leaguer_id'], [
                'order_id' => $order_id,
                'mobile' => $param['mobile'],
                'pay_price' => 0,
                'goods_id' => $goods_info['GoodsID'],
                'goods_name' => $goods_info['GoodsName'],
                'goods_price' => 0,
                'num' => 1,
            ], 6);
            $this->ylb_service->orderPayAndConfirm([
                'order_id' => $order_id,
            ]);
            //获取创建的套票信息
            $ylb_order_info = $this->ylb_service->getTicketInfoByOrderId($order_id)['LgPagTitDetails'];
        }else{ //套票商品
            //创建订单
            $this->ylb_service->createOrder($user['leaguer_id'], [
                'order_id' => $order_id,
                'mobile' => $param['mobile'],
                'pay_price' => $goods_info['GoodsPrice'],
                'goods_id' => $goods_info['GoodsID'],
                'goods_name' => $goods_info['GoodsName'],
                'goods_price' => $goods_info['GoodsPrice'],
                'num' => 1,
            ]);
            //订单完成
            $this->ylb_service->orderPayAndCompleted([
                'order_id' => $order_id,
            ]);
            //获取创建的套票信息
            $ylb_order_info = $this->ylb_service->getTicketInfoByOrderId($order_id)['LgPagTitDetails'][0];
        }
        Db::startTrans();
        try {
            if($goods_info['GoodsType'] == 102){
                //写入订单记录
                $order = new WdXcxUserTicketOrderLists();
                $order->save([
                    'uniacid' => $this->uniacid,
                    'user_id' => $user['id'],
                    'order_id' => $order_id,
                    'user_mobile' => $param['mobile'],
                    'status' => 1,
                    'ticket_name' => $goods_info['GoodsName'],
                    'ticket_thumb' => $info['ticket_thumb'],
                    'ticket_lable' => $info['ticket_lable'],
                    'ticket_price' => $goods_info['GoodsPrice'],
                    'pay_price' => $pay_price_info['need_pay_price'],
                    'ticket_id' => $goods_info['GoodsID'],
                    'ticket_code' => $ylb_order_info['TicketID'],
                    'charge_mode' => $ylb_order_info['ChargeMode'],
                    'ticket_past_time' => strtotime($ylb_order_info['PastDueTime']),
                    'ticket_details_list' => $ylb_order_info['DetailsList'],
                    'ticket_descs' => $info['ticket_descs'],
                    'pay_info' => $pay_price_info,
                    'pay_type' => $pay_type,
                    'user_coupon_id' => $param['user_coupon_id'],
                    'coupon_minus_money' => $pay_price_info['coupon_minus_money'],
                ]);
                WdXcxUserOrderLists::create([
                    'uniacid' => $this->uniacid,
                    'user_id' => $user['id'],
                    'user_mobile' => $param['mobile'],
                    'order_id' => $order_id,
                    'orderform_id' => $order->id,
                    'orderform_type' => WdXcxUserOrderLists::ORDER_TYPE_TICKET,
                    'status' => WdXcxUserOrderLists::ORDER_STAUS_1,
                ]);
            }else{
                foreach ($ylb_order_info as $k => $item){
                    //写入订单记录
                    $order = new WdXcxUserTicketOrderLists();
                    $order->save([
                        'uniacid' => $this->uniacid,
                        'user_id' => $user['id'],
                        'order_id' => $order_id.'-'.($k+1),
                        'user_mobile' => $param['mobile'],
                        'status' => 1,
                        'ticket_name' => $item['TicketName'],
                        'ticket_thumb' => $info['ticket_thumb'],
                        'ticket_lable' => $info['ticket_lable'],
                        'ticket_price' => $goods_info['GoodsPrice'],
                        'pay_price' => $k == 0 ? $pay_price_info['need_pay_price'] : 0,
                        'ticket_id' => $goods_info['GoodsID'],
                        'ticket_code' => $item['TicketID'],
                        'charge_mode' => $item['ChargeMode'],
                        'ticket_past_time' => strtotime($item['PastDueTime']),
                        'ticket_details_list' => $item['DetailsList'],
                        'ticket_descs' => $info['ticket_descs'],
                        'pay_info' => $pay_price_info,
                        'pay_type' => $pay_type,
                        'user_coupon_id' => $param['user_coupon_id'],
                        'coupon_minus_money' => $k == 0 ? $pay_price_info['coupon_minus_money'] : 0,
                    ]);
                    WdXcxUserOrderLists::create([
                        'uniacid' => $this->uniacid,
                        'user_id' => $user['id'],
                        'user_mobile' => $param['mobile'],
                        'order_id' => $order_id.'-'.($k+1),
                        'orderform_id' => $order->id,
                        'orderform_type' => WdXcxUserOrderLists::ORDER_TYPE_TICKET,
                        'status' => WdXcxUserOrderLists::ORDER_STAUS_1,
                    ]);
                }
            }
            $user = (new WdXcxUser())->getUserById($user['id']);
            if($pay_type == 2){
                //零钱消费流水
                (new WdXcxUserGiveBalanceRecord())->addRecord($user, [
                    'change_price' => $pay_price_info['need_pay_price'],
                    'order_id' => $order_id,
                    'message' => '购买套票',
                    'user_id' => $user['id'],
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_TICKET
                ]);
            }elseif ($pay_type == 0){
                //余额消费流水
                (new WdXcxUserBalanceRecord())->addRecord($user, [
                    'change_price' => $pay_price_info['need_pay_price'],
                    'order_id' => $order_id,
                    'message' => '购买套票',
                    'user_id' => $user['id'],
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_TICKET
                ]);
            }
            if ($pay_type == 1){
                //微信支付记录
                WdXcxUserWxpayRecord::create([
                    'uniacid' => $this->uniacid,
                    'user_id' => $user['id'],
                    'order_id' => $order_id,
                    'pay_price' => $pay_price_info['need_pay_price'],
                    'create_time' => time(),
                    'order_type' => WdXcxUserWxpayRecord::WX_PAY_TICKETING_ORDER
                ]);
                //套票分销订单
                (new DistributionService(app()))->createFxOrder($user, $order_id, $order->id, WdXcxUserOrderLists::ORDER_TYPE_TICKET, $pay_price_info['need_pay_price'], 1);
            }
            //使用优惠券
            if(!empty($param['user_coupon_id'])){
                (new WdXcxUserCoupon())->useUserCoupon($this->uniacid, $user['id'], $param['user_coupon_id'], WdXcxUserCoupon::USE_COUPON_TICKETING);
            }
        }catch (\Exception $exception){
            Db::rollback();
            Log::info('购买套票失败'.json_encode([
                    'user_id' => $user['id'],
                    'order_id' => $order_id,
                    'goods_id' => $goods_info['GoodsID'],
                    'goods_name' => $goods_info['GoodsName'],
                    'pay_price' => $pay_price_info['need_pay_price'],
                ]));
            throwError($exception->getMessage());
        }
        Db::commit();
    }

    /**检查购买金额
     * @param $param
     * @param $info
     * @param $goods_info
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function checkByGoods($param, $info, $goods_info)
    {
        $user_model = new WdXcxUser();
        if($info->vip_discount){
            $discount_info = $user_model->getUserDiscountPrice($user_model->getUserByOpenid(request()->userOpenid()), $goods_info['GoodsPrice']);
            $need_pay_price = $discount_info['discount_price'];
            $vip_discount = $discount_info['discount'];
        }else{
            $need_pay_price = $goods_info['GoodsPrice'];
            $vip_discount = 0;
        }
        $pay_type = isset($param['pay_type']) ? $param['pay_type'] : 0;
        $coupon_minus_money = 0;
        if($param['user_coupon_id']){
            list($need_pay_price, $coupon_minus_money) = (new WdXcxUserCoupon())->checkOrderUseCoupon(request()->userID(), $param['user_coupon_id'], $need_pay_price);
        }
        if(bccomp($param['pay_price'], $need_pay_price, 2) !== 0){
            throwError('订单金额异常');
        }
        $user = (new WdXcxUser())->getUserById(request()->userID());
        if($pay_type == 2){ //零钱支付
            if(bccomp($user->give_balance, $need_pay_price, 2) == -1){
                throwError('用户零钱余额不足');
            }
        }else if($pay_type == 0){ //余额支付
            $user_balance = (new UserService(app()))->getUserProperty(1)['balance'];
            $user_balance = str_replace(',', '', $user_balance);
            if(bccomp($user_balance, $need_pay_price, 2) == -1){
                throwError('用户余额不足');
            }
        }
        return compact('need_pay_price', 'vip_discount', 'user_balance', 'user', 'coupon_minus_money');
    }

    /**获取商品详情
     * @param $goods_id
     * @return mixed
     * @throws \cores\exception\BaseException
     */
    private function getGoodsInfoFromYlb($goods_id)
    {
        $info = $this->ylb_service->getAllShopGoodsLists($goods_id);
        if(empty($info)){
            throwError('指定详情不存在');
        }
        return $info[0];
    }

    /**用户套票订单列表
     * @param $param
     * @param $user_id
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getUserOrderLists($param, $user_id)
    {
        $this->checkUserTicketRemote();
        $lists = WdXcxUserTicketOrderLists::where([
            'uniacid' => $this->uniacid,
            'user_id' => $user_id
        ])->where(function ($query)use($param){
            if($param['type']){
                $query->where('status', $param['type']);
            }
        })->withoutField('ticket_details_list, pay_info, ticket_id')
            ->order('id desc')
            ->paginate(10)->each(function ($item){
                $item->status;
                $item->ticket_past_time_str = date('Y-m-d H:i:s', $item->ticket_past_time);
            });
        return $lists;
    }

    /**同步用户远程套票信息
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function checkUserTicketRemote()
    {
        $user_id = request()->userID();
        $ylb_service = new YlbApiService();
        $user_ticket = $ylb_service->getUserTicketLists(request()->leaguerID());
        Log::info('用户远程套票信息'.json_encode($user_ticket, JSON_UNESCAPED_UNICODE));
        $user = (new WdXcxUser())->getUserById($user_id);
        if(count($user_ticket) > 0){
            foreach ($user_ticket as $ticket){
                $has = WdXcxUserTicketOrderLists::where([
                    'user_id' => $user_id,
                    'ticket_code' => $ticket['ID'],
                ])->find();
                if(!$has){
                    Db::startTrans();
                    try {
                        $order_id = generateOrderId('T');
                        $ticket_order_data = [
                            'uniacid' => $this->uniacid,
                            'user_id' => request()->userID(),
                            'order_id' => $order_id,
                            'user_mobile' => $user->mobile,
                            'status' => $ticket['Status'] == 1 ? 2 : 1,
                            'ticket_name' => $ticket['PackageTicket'],
                            'ticket_thumb' => '',
                            'ticket_lable' => '',
                            'ticket_price' => 0,
                            'pay_price' => 0,
                            'ticket_id' => -1,
                            'ticket_code' => $ticket['ID'],
                            'charge_mode' => $ticket['ChargeMode'],
                            'ticket_past_time' => strtotime($ticket['PastDueTime']),
                            'ticket_details_list' => $ticket['DetailsList'],
                            'ticket_descs' => '同步远程套票数据',
                            'pay_info' => '',
                        ];
                        $ticket_order_model = new WdXcxUserTicketOrderLists();
                        $ticket_order_model->save($ticket_order_data);
                        $user_order_data = [
                            'uniacid' => $this->uniacid,
                            'user_id' => request()->userID(),
                            'user_mobile' => $user->mobile,
                            'order_id' => $order_id,
                            'orderform_id' => $ticket_order_model->id,
                            'orderform_type' => WdXcxUserOrderLists::ORDER_TYPE_TICKET,
                            'status' => $ticket['Status'] == 1 ? WdXcxUserOrderLists::ORDER_STAUS_2 : WdXcxUserOrderLists::ORDER_STAUS_1,
                        ];
                        $user_order_model = new WdXcxUserOrderLists();
                        $user_order_model->save($user_order_data);
                    }catch (\Exception $exception){
                        Db::rollback();
                        \think\Log::info('同步用户远程套餐数据失败:'.$exception->getMessage());
                    }
                    Db::commit();
                }
            }
        }
    }

}