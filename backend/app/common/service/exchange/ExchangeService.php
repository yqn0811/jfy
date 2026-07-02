<?php

namespace app\common\service\exchange;

use app\common\model\gift_exchange\WdXcxExchangeInfo;
use app\common\model\order\WdXcxUserExchangeOrderLists;
use app\common\model\order\WdXcxUserOrderLists;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserBalanceRecord;
use app\common\model\user\WdXcxUserDiamondRecord;
use app\common\model\user\WdXcxUserIntegralRecord;
use app\common\service\BaseService;
use app\common\service\ylb\YlbApiService;
use cores\utils\Utils;
use think\App;
use think\Exception;
use think\facade\Db;

class ExchangeService extends BaseService
{
    private $exchange_model;
    private $exchange_order_model;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->exchange_model = new WdXcxExchangeInfo();
        $this->exchange_order_model = new WdXcxUserExchangeOrderLists();
    }

    /**获取积分兑换列表
     * @param $type
     * @return WdXcxExchangeInfo[]|array|\think\Collection
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGiftLists()
    {
        return $this->exchange_model->getGiftListsByType($this->uniacid);
    }

    /**获取实物兑换分类
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGiftCates()
    {
        $cates = Db::name('wd_xcx_score_cate')
            ->where('uniacid', $this->uniacid)
            ->field('id, name')
            ->order('num desc')
            ->select()->toArray();
        array_unshift($cates, ['id' => 0, 'name' => '全部']);
        return $cates;
    }

    /**按分类获取指定实物列表
     * @param $param
     * @return WdXcxExchangeInfo[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGiftCateGoods($param)
    {
        return $this->exchange_model->where('uniacid', $this->uniacid)
            ->where('exchange_type', 3)
            ->where(function ($query)use($param){
                if(!empty($param['cate_id'])){
                    $query->where('cate_id', $param['cate_id']);
                }
            })
            ->order('sort_num desc, id desc')
            ->field('id, uniacid, pay_score, gift_title, gift_thumb, gift_stock')
            ->select();
    }

    /**获取兑换商品详情
     * @param $id
     * @return WdXcxExchangeInfo|array|mixed|\think\Model|null
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGiftInfo($id)
    {
        return $this->exchange_model->getGiftInfoById($this->uniacid, $id);
    }

    /**创建用户兑换订单
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createExchangeOrder($param, $user_id)
    {
        $info = $this->exchange_model->getGiftInfoById($this->uniacid, $param['goods_id']);
        $user = (new WdXcxUser())->getUserById($user_id);
        $this->checkExchange($info, $user);
        $this->createOrderHandler($info, $user);
    }

    /**创建兑换订单数据记录
     * @param $exchange
     * @param $user
     * @return void
     * @throws \cores\exception\BaseException
     */
    private function createOrderHandler($exchange, $user)
    {
        Db::startTrans();
        $order_id = generateOrderId('E');
        $exchange_order = new WdXcxUserExchangeOrderLists();
        $exchange_order->uniacid = $this->uniacid;
        $exchange_order->user_id = $user->id;
        $exchange_order->order_id = $order_id;
        $exchange_order->status = 2;
        $exchange_order->exchange_type = $exchange->exchange_type;
        try {
            if($exchange->exchange_type == 1 || $exchange->exchange_type == 2){
                $exchange_order->pay_diamond = $exchange->pay_diamond;
                $exchange_order->check_time = time();
                (new WdXcxUserDiamondRecord())->addRecord($user, [
                    'order_id' => $order_id,
                    'change_diamond' => $exchange->pay_diamond,
                    'message' => $exchange->exchange_type == 1 ? '钻石兑换余额' : '钻石兑换积分',
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_EXCHANGE,
                ]);
                if($exchange->exchange_type == 1){
                    (new WdXcxUserBalanceRecord())->addRecord($user, [
                        'order_id' => $order_id,
                        'change_price' => $exchange->get_balance,
                        'message' => '钻石兑换余额',
                        'user_id' => $user->id,
                        'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_EXCHANGE,
                    ], WdXcxUserBalanceRecord::BALANCE_CHANGE_ADD);
                    $exchange_order->get_balance = $exchange->get_balance;
                }else{
                    (new WdXcxUserIntegralRecord())->addRecord($user, [
                        'order_id' => $order_id,
                        'change_integral' => $exchange->get_score,
                        'message' => '钻石兑换积分',
                        'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_EXCHANGE
                    ], WdXcxUserIntegralRecord::INTEGRAL_CHANGE_ADD);
                    $exchange_order->get_score = $exchange->get_score;
                }
            }elseif ($exchange->exchange_type == 4) { //彩票兑换积分
                (new WdXcxUserIntegralRecord())->addRecord($user, [
                    'order_id' => $order_id,
                    'change_integral' => $exchange->get_lottery_score,
                    'message' => '彩票兑换积分',
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_EXCHANGE
                ], WdXcxUserIntegralRecord::INTEGRAL_CHANGE_ADD);
                $exchange_order->get_score = $exchange->get_lottery_score;
                $exchange_order->pay_lottery = $exchange->pay_lottery;
                //修改用户彩票
                (new YlbApiService())->changeUserValues($user->leaguer_id, 403, bcsub(0, $exchange->pay_lottery),  '彩票兑换积分');
            }else{
                $exchange = $this->exchange_model->where('id', $exchange->id)->lock(true)->find();
                if($exchange->gift_stock != -1 && $exchange->gift_stock < 1){
                    throw new Exception('该礼品库存不足');
                }
                $exchange_order->status = 1;
                $exchange_order->pay_score = $exchange->pay_score;
                $exchange_order->gift_title = $exchange->gift_title;
                $exchange_order->gift_thumb = $exchange->gift_thumb;
                $exchange_order->gift_pics = $exchange->gift_pics;
                $exchange_order->gift_info = $exchange->gift_info;
                (new WdXcxUserIntegralRecord())->addRecord($user, [
                    'order_id' => $order_id,
                    'change_integral' => $exchange->pay_score,
                    'message' => '积分兑换实物',
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_EXCHANGE
                ]);
                if($exchange->gift_stock != -1){
                    $exchange->dec('gift_stock')->update();
                }
            }
            $exchange_order->order_log = [
                [
                    'create_time' => date('Y-m-d H:i:s'),
                    'order_log' => '订单创建',
                ]
            ];
            $exchange_order->save();
            WdXcxUserOrderLists::create([
                'uniacid' => $this->uniacid,
                'user_id' => $user->id,
                'user_mobile' => $user->mobile,
                'order_id' => $order_id,
                'orderform_id' => $exchange_order->id,
                'orderform_type' => WdXcxUserOrderLists::ORDER_TYPE_EXCHANGE,
                'status' => $exchange->exchange_type == 3 ? WdXcxUserOrderLists::ORDER_STAUS_1 : WdXcxUserOrderLists::ORDER_STAUS_2,
            ]);
        }catch (Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
    }

    /**检测兑换条件是否满足
     * @param $exchange
     * @param $user
     * @return void
     * @throws \cores\exception\BaseException
     */
    private function checkExchange($exchange, $user)
    {
        if($exchange->exchange_type == 1){ //兑换余额
            if($user->diamond < $exchange->pay_diamond){
                throwError('用户钻石不足');
            }

        }elseif ($exchange->exchange_type == 2){ //钻石兑换积分
            if($user->diamond < $exchange->pay_diamond){
                throwError('用户钻石不足');
            }
        }elseif ($exchange->exchange_type == 3){ //兑换实物
            if($user->integral < $exchange->pay_score){
                throwError('用户积分不足');
            }
        }else{ //彩票兑换积分
            if($user->UserLottery < $exchange->pay_lottery){
                throwError('用户彩票不足');
            }
        }
    }

    /**获取兑换订单列表
     * @param $param
     * @param $user_id
     * @return \think\Paginator
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserExchangeOrderLists($param, $user_id)
    {
        $type = $param['type'] ? $param['type'] : 0;
        if(!in_array($type, [0,1,2])){
            throwError('参数错误');
        }
        $lists = $this->exchange_order_model->where('uniacid', $this->uniacid)
            ->where('user_id', $user_id)
            ->where(function ($query)use($type){
                if($type){
                    $query->where('status', $type);
                }
            })->order('id desc')
            ->paginate(10);
        return $lists;
    }

    /**获取订单核销二维码
     * @param $param
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserExchangeOrderQrcode($param, $user_id)
    {
        $order_id = $param['order_id'];
        $order = $this->exchange_order_model->where('uniacid', $this->uniacid)
            ->where('user_id', $user_id)
            ->where('order_id', $order_id)
            ->find();
        if(!$order){
            throwError('订单不存在');
        }
        if($order->exchange_type != 3){
            throwError('订单类型错误');
        }
        if($order->status != 1){
            throwError('订单当前状态不能使用');
        }
        return [
            'qrcode' => Utils::createQrcode($order->order_id, '', true)
        ];
    }

    /**订单核销
     * @param $uniacid
     * @param $order_id
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkExchangeOrder($uniacid, $order_id, $message)
    {
        $order = $this->exchange_order_model->where('uniacid', $uniacid)
            ->where('order_id', $order_id)
            ->find();
        if(!$order){
            throwError('订单不存在');
        }
        if($order->exchange_type != 3){
            throwError('订单类型错误');
        }
        if($order->status != 1){
            throwError('订单当前状态不能使用');
        }
        Db::startTrans();
        try {
            $order->status = 2;
            $order->order_log = array_merge($order->order_log, [[
                'create_time' => date('Y-m-d H:i:s'),
                'order_log' => $message,
            ]]);
            $order->check_time = time();
            $order->save();
            $order->mainOrder->status = 2;
            $order->mainOrder->save();
        }catch (Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
    }
}