<?php

namespace app\common\service\catering;

use app\common\model\catering\WdXcxFood;
use app\common\model\catering\WdXcxFoodCate;
use app\common\model\catering\WdXcxFoodTypeValue;
use app\common\model\coupon\WdXcxUserCoupon;
use app\common\model\order\WdXcxUserCateringOrderDishes;
use app\common\model\order\WdXcxUserCateringOrderLists;
use app\common\model\order\WdXcxUserOrderLists;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserBalanceRecord;
use app\common\model\user\WdXcxUserWxpayRecord;
use app\common\service\BaseService;
use app\common\service\distribution\DistributionService;
use app\common\service\pay\PayService;
use app\common\service\printing\PrintingService;
use think\App;
use think\cache\driver\Redis;
use think\Exception;
use think\facade\Db;

class CateringService extends BaseService
{
    private $cate_model;
    private $food_model;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->cate_model = new WdXcxFoodCate();
        $this->food_model = new WdXcxFood();
    }

    /**获取分类列表
     * @return WdXcxFoodCate[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCateringCateLists()
    {
        return WdXcxFoodCate::where([
            'uniacid' => $this->uniacid,
            'flag' => 1
        ])->order('num desc, id desc')
            ->field('id, title')
            ->select();
    }

    /**获取菜品列表
     * @param $param
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getCateringDishes($param)
    {
        $cate_id = empty($param['cate_id']) ? 0 : $param['cate_id'];
        return $this->food_model->where('uniacid', $this->uniacid)
            ->where('status', 1)
            ->where(function ($query)use($cate_id){
                if($cate_id > 0){
                    $query->where('cid', $cate_id);
                }
            })->field('id, uniacid, thumb, title')
            ->order('num desc, id desc')
            ->paginate(10)->each(function ($item){
                $item->price = $item->ShowPrice;
                $item->month_sale = $item->MonthSaleCount;
            });
    }

    /**获取菜品详情数据
     * @param $param
     * @return WdXcxFood|array|mixed|\think\Model|null
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCateringDishesDetail($param)
    {
        if(empty($param['dishes_id'])){
            throwError('菜品ID不能为空');
        }
        $info = $this->food_model->getDishesById($param['dishes_id']);
        $info->specs_data = $info->SpecsData;
        $info->specs = $info->specs;
        $info->month_sale = $info->MonthSaleCount;
        return $info;
    }

    /**创建点餐订单
     * @param $param
     * @param $user_id
     * @return string
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createCateringDishesOrder($param, $user_id)
    {
        if(empty($param['dishes'])){
            throwError('请选择需要购买的菜品');
        }
        $dishes = json_decode($param['dishes'], true);
        if(!$dishes || count($dishes) < 1){
            throwError('请先选择菜品');
        }
        $user = (new WdXcxUser())->getUserById($user_id);
        $price = $this->getTotalPrice($dishes, $user, true, $param['user_coupon_id']);
        if(bccomp($param['pay_price'], $price['pay_price'], 2) !== 0){
            throwError('订单金额异常');
        }
        if($param['pay_type'] == 0){
            $UserBalance = str_replace(',', '', $user->UserBalance);
            if(bccomp($UserBalance, $price['pay_price'], 2) == -1){
                throwError('用户余额不足');
            }
        }
        return $this->createCateringOrderHandler($price, $param, $user);
    }

    /**餐饮订单数据
     * @param $price_data
     * @param $param
     * @param $user
     * @return string
     * @throws \think\db\exception\DbException
     */
    private function createCateringOrderHandler($price_data, $param, $user)
    {
        $order_id = generateOrderId('F');
        $catering_order_data = [
            'uniacid' => $this->uniacid,
            'user_id' => $user->id,
            'order_id' => $order_id,
            'order_type' => empty($param['table_num']) ? 2 : 1,
            'status' => 1,
            'total_price' => $price_data['total_price'],
            'pay_price' => $price_data['pay_price'],
            'user_mobile' => $param['mobile'] ? $param['mobile'] : $user->mobile,
            'order_remark' => $param['remark'],
            'take_time' => $param['take_time'],
            'take_timestamp' => $param['take_time'] ? strtotime(date('Y-m-d').' '.$param['take_time']) : 0,
            'table_number' => $param['table_num'],
            'price_info' => $price_data,
            'user_coupon_id' => $param['user_coupon_id'],
            'coupon_minus_money' => $price_data['coupon_minus_money'],
            'order_number' => 0,
            'order_log' => [
                [
                    'create_time' => time(),
                    'log' => '订单创建'
                ]
            ]
        ];
        if($catering_order_data['order_type'] == 2){
            $today_order_count = WdXcxUserCateringOrderLists::where('order_type', 2)
                ->whereTime('create_time', 'today')
                ->count();
            $catering_order_data['order_number'] = $today_order_count+1;
        }
        $order_dishes = [];
        foreach ($price_data['discount_info'] as $item){
            $foods = $item['foods'];
            $type = $item['foods_type'];
            $order_dishes[] = [
                'uniacid' => $this->uniacid,
                'user_id' => $user->id,
                'order_id' => $order_id,
                'dishes_id' => $foods->id,
                'dishes_title' => $foods->title,
                'dishes_thumb' => $foods->thumb,
                'spec_id' => $type->id,
                'spec_value' => $type->SpecsValue,
                'num' => $item['num'],
                'dishes_price' => $type->price,
                'pay_price' => $item['discount_price'],
            ];

        }
        $user_order_data = [
            'uniacid' => $this->uniacid,
            'user_id' => $user->id,
            'order_id' => $order_id,
            'user_mobile' => $catering_order_data['user_mobile'],
            'orderform_type' => WdXcxUserOrderLists::ORDER_TYPE_CATERING,
            'status' => 1
        ];
        $redis = new Redis(GetRedisConf());
        $redis->set('catering_order_'.$order_id, json_encode(compact('catering_order_data', 'order_dishes', 'user_order_data'), JSON_UNESCAPED_UNICODE), 300);
        return $order_id;
    }

    /**余额支付完成
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cateringDishesOrderPay($param, $user_id)
    {
        if(empty($param['order_id'])){
            throwError('订单ID不能为空');
        }
        $has = WdXcxUserCateringOrderLists::where('order_id', $param['order_id'])->find();
        if($has){
            throwError('订单状态不正确或已支付');
        }
        $redis = new Redis(GetRedisConf());
        $order_data = $redis->get('catering_order_'.$param['order_id']);
        if(!$order_data){
            throwError('订单不存在');
        }
        $order_data = json_decode($order_data, true);
        $user = (new WdXcxUser())->getUserById($user_id);
        $UserBalance = str_replace(',', '', $user->UserBalance);
        if(bccomp($UserBalance, $order_data['catering_order_data']['pay_price'], 2) == -1){
            throwError('用户余额不足');
        }
        $this->saveCateringOrder($param['order_id'], $user, $order_data);
        $redis->delete('catering_order_'.$param['order_id']);
    }

    /**获取微信支付参数
     * @param $param
     * @param $user_id
     * @return array
     * @throws \cores\exception\BaseException
     */
    public function getCateringDishesOrderWxPay($param, $openid)
    {
        if(empty($param['order_id'])){
            throwError('订单ID不能为空');
        }
        $has = WdXcxUserCateringOrderLists::where('order_id', $param['order_id'])->find();
        if($has){
            throwError('订单状态不正确或已支付');
        }
        $redis = new Redis(GetRedisConf());
        $order_data = $redis->get('catering_order_'.$param['order_id']);
        if(!$order_data){
            throwError('订单不存在');
        }
        $order_data = json_decode($order_data, true);
        $pay_info = (new PayService($this->app))->getPayInfo([
            'order_id' => $param['order_id'],
            'pay_price' => $order_data['catering_order_data']['pay_price'],
            'subject' => '点餐订单',
            'openid' => $openid,
        ]);
        return [
            'timeStamp' => $pay_info['payTimeStamp'],
            'nonceStr' => $pay_info['paynonceStr'],
            'package' => $pay_info['payPackage'],
            'signType' => $pay_info['paySignType'],
            'paySign' => $pay_info['paySign'],
        ];
    }

    /**订单支付完成 回调处理
     * @param $order_id
     * @param $user
     * @param $order_data
     * @param $pay_type
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function saveCateringOrder($order_id, $user, $order_data, $pay_type=0)
    {
        $catering_order_data = $order_data['catering_order_data'];
        $order_dishes = $order_data['order_dishes'];
        $user_order_data = $order_data['user_order_data'];
        $catering_order = new WdXcxUserCateringOrderLists();
        $dishes_order = new WdXcxUserCateringOrderDishes();
        $user_order = new WdXcxUserOrderLists();
        Db::startTrans();
        try {
            //处理规格库存
            foreach ($catering_order_data['price_info']['discount_info'] as $item){
                $type_value = WdXcxFoodTypeValue::where('id', $item['value_id'])->find();
                $type_value->salenum = $type_value->salenum + $item['num'];
                $type_value->kc = $type_value->kc - $item['num'] < 0 ? 0 : $type_value->kc - $item['num'];
                $type_value->save();
//                WdXcxFoodTypeValue::where('id', $item['value_id'])
//                    ->inc('salenum', $item['num'])
//                    ->dec('kc', $item['num'])
//                    ->update();
            }
            //改变用户余额记录
            if($pay_type == 0){
                (new WdXcxUserBalanceRecord())->addRecord($user, [
                    'order_id' => $order_id,
                    'change_price' => $catering_order_data['pay_price'],
                    'message' => '点餐消费',
                    'user_id' => $user->id,
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_CATERING,
                ]);
                $catering_order_data['order_log'] = [
                    [
                        'create_time' => time(),
                        'log' => '订单创建,用户余额支付'
                    ]
                ];
                $catering_order_data['pay_type'] = 0;
            }else{
                $catering_order_data['pay_type'] = 1;
                $catering_order_data['order_log'] = [
                    [
                        'create_time' => time(),
                        'log' => '订单创建,用户微信在线支付'
                    ]
                ];
                //保存用户微信消费记录
                WdXcxUserWxpayRecord::create([
                    'uniacid' => $this->uniacid,
                    'user_id' => $catering_order_data['user_id'],
                    'order_id' => $order_id,
                    'pay_price' => $catering_order_data['pay_price'],
                    'create_time' => time(),
                    'order_type' => WdXcxUserWxpayRecord::WX_PAY_CATEGORY_ORDER
                ]);
            }
            $catering_order->save($catering_order_data);
            $dishes_order->saveAll($order_dishes);
            $user_order_data['orderform_id'] = $catering_order->id;
            $user_order->save($user_order_data);
            if($pay_type == 1){
                //点餐分销订单
                $user = $user ? $user : (new WdXcxUser())->getUserById($catering_order_data['user_id']);
                (new DistributionService(app()))->createFxOrder($user, $order_id, $catering_order->id, WdXcxUserOrderLists::ORDER_TYPE_CATERING, $catering_order_data['pay_price'], 0);
            }
            //使用优惠券
            if(!empty($catering_order_data['user_coupon_id'])){
                (new WdXcxUserCoupon())->useUserCoupon($this->uniacid, $catering_order_data['user_id'], $catering_order_data['user_coupon_id'], WdXcxUserCoupon::USE_COUPON_RESTAURANT);
            }
        }catch (Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
        PrintingService::PrintingOrder($this->uniacid, $catering_order);
    }

    /**用户点餐订单列表
     * @param $param
     * @param $user_id
     * @return \think\Paginator
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserCateringOrderLists($param, $user_id)
    {
       $type = empty($param['type']) ? 0 : $param['type'];
       if(!in_array($type, [0,1,2,3])){
           throwError('参数不正确');
       }
       $lists = WdXcxUserCateringOrderLists::with(['dishes' => function($query){
           $query->withoutField('create_time, update_time, delete_time, id, dishes_id');
       }])
           ->where('user_id', $user_id)
           ->where(function ($query)use($type){
               if($type){
                   $query->where('status', $type);
               }
           })->whereBetween('create_time', [strtotime('-90 days'), time()])
           ->withSum('dishes', 'num')
           ->withoutField('price_info, update_time, delete_time')
           ->order('id desc')
           ->paginate(10);
       return $lists;
    }

    public function getUserCateringOrderDetail($param, $user_id)
    {
        if(empty($param['order_id'])){
            throwError('参数不完整');
        }
        $order = WdXcxUserCateringOrderLists::with('dishes')
            ->where('uniacid', $this->uniacid)
            ->where('order_id', $param['order_id'])
            ->where('user_id', $user_id)
            ->withoutField('price_info, order_log')
            ->find();
        if(!$order){
            throwError('订单不存在');
        }
        return $order;
    }

    /**获取购物车菜品价格
     * @param $param
     * @param $user_id
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCateringDishesCartPrice($param, $user_id)
    {
        if(empty($param['dishes'])){
            throwError('请选择需要购买的菜品');
        }
        $dishes = json_decode($param['dishes'], true);
        $user = (new WdXcxUser())->getUserById($user_id);
        $result = $this->getTotalPrice($dishes, $user);
        $result['start_time'] = date('H:i');
        $result['end_time'] = '22:00';
        $UserBalance = str_replace(',', '', $user->UserBalance);
        $result['user_balance'] = $UserBalance;
        $result['give_balance'] = $user->give_balance;
        return $result;
    }

    /**获取菜品价格
     * @param $dishes
     * @param $user
     * @param $info bool 是否返回优惠信息
     * @param $user_coupon_id int 用户代金券id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getTotalPrice($dishes, $user, $info=false, $user_coupon_id=0)
    {
        $total_price = 0;
        $pay_price = 0;
        $discount_info = [];
        foreach ($dishes as $item){
            $pro = $this->food_model->where('id', $item['pid'])->find();
            if(!$pro){
                $this->result(['pid' => $item['pid']], 4000, '菜品不存在');
            }
            if($pro->status != 1){
                $this->result(['pid' => $item['pid']], 4000, '菜品'.$pro->title.'已下架');
            }
            $type = $pro->specs()->where('id', $item['value_id'])->find();
            if(!$type){
                $this->result(['pid' => $item['pid']], 4000, '菜品'.$pro->title.'所选规格已下架');
            }
            //检查库存
            if($type->kc < $item['num']){
                $this->result(['pid' => $item['pid']], 4000, '菜品'.$pro->title.'库存不足');
            }
            $pro_price = bcmul($type->price, $item['num'], 2);
            $total_price = bcadd($total_price, $pro_price, 2);
            $item['foods'] = $pro;
            $item['foods_type'] = $type;
            if($pro->vip_dis == 1){
                $discount_price = $user->getUserDiscountPrice($user, $pro_price);
                $pro_price = $discount_price['discount_price'];
                $discount_info[] = array_merge($item, $discount_price);
            }else{
                $discount_info[] = array_merge($item, [
                    'discount_price' => $pro_price,
                    'discount' => '-1',
                ]);
            }
            $pay_price = bcadd($pay_price, $pro_price, 2);
        }
        $discount_price = bcsub($total_price, $pay_price, 2);
        $coupon_minus_money = 0;
        if($user_coupon_id){
            list($pay_price, $coupon_minus_money) = (new WdXcxUserCoupon())->checkOrderUseCoupon($user->id, $user_coupon_id, $pay_price);
        }
        $result = compact('total_price', 'pay_price', 'discount_price', 'coupon_minus_money');
        if($info){
            $result['discount_info'] = $discount_info;
        }
        return $result;
    }

    /**订单核销
     * @param $order_id
     * @param $from
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkOutUserOrder($order_id, $from=1)
    {
        $order = WdXcxUserCateringOrderLists::where([
            'uniacid' => $this->uniacid,
            'order_id' => $order_id,
        ])->find();
        if(!$order){
            throwError('订单不存在');
        }
        if($order->status != 1){
            throwError('订单状态不正确');
        }
        Db::startTrans();
        try {
            $order->status = 3;
            $order_log = $order->order_log;
            if($order_log){
                $order_log = array_merge($order_log, [
                    [
                        'create_time' => time(),
                        'log' => $from == 1 ? '订单后台核销' : '订单扫码核销',
                    ]
                ]);
            }else{
                $order_log = [
                    [
                        'create_time' => time(),
                        'log' => $from == 1 ? '订单后台核销' : '订单扫码核销',
                    ]
                ];
            }
            $order->order_log = $order_log;
            WdXcxUserOrderLists::where('order_id', $order->order_id)->update(['status' => 2]);
            $order->save();
            //修改分销订单状态
            (new DistributionService(app()))->changeFxOrderStatus($order->order_id, 1);
        }catch (Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
    }

    /**餐饮订单取消
     * @param $order_id
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cancelUserOrder($order_id)
    {
        $order = WdXcxUserCateringOrderLists::where([
            'uniacid' => $this->uniacid,
            'order_id' => $order_id,
        ])->find();
        if(!$order){
            throwError('订单不存在');
        }
        if($order->status != 1){
            throwError('订单状态不正确');
        }
        Db::startTrans();
        try {
            $order->status = -1;
            $order_log = $order->order_log;
            $order_log = array_merge($order_log, [
                [
                    'create_time' => time(),
                    'log' => '后台取消订单',
                ]
            ]);
            $order->order_log = $order_log;
            WdXcxUserOrderLists::where('order_id', $order->order_id)->update(['status' => -1]);
            $order->save();
            //处理库存
            foreach ($order->dishes as $dish){
                $type = WdXcxFoodTypeValue::where('id', $dish->spec_id)->find();
                $type->kc = $type->kc + $dish->num;
                $type->salenum = $type->salenum - $dish->num < 0 ? 0 : $type->salenum - $dish->num;
                $type->save();
            }
            //处理优惠券
            if($order->user_coupon_id > 0){
                $user_coupon = WdXcxUserCoupon::where('id', $order->user_coupon_id)->find();
                if($user_coupon){
                    $user_coupon->use_status = 1;
                    $user_coupon->use_type = '';
                    $user_coupon->save();
                }
            }
            if($order->pay_type == 1){
                $record = WdXcxUserWxpayRecord::where('order_id', $order_id)->find();
                $refund_order_id = $order_id.'R'.rand(1000, 9999);
                (new PayService($this->app))->refund([
                    'refund_order_id' => $refund_order_id,
                    'refund_price' => $order->pay_price,
                    'origOrderNo' => $order_id,
                ]);
                if($record){
                    $record->refund_order_id = $refund_order_id;
                    $record->save();
                    $record->delete();
                }
            }else{
                //余额消费流水
                $user = (new WdXcxUser())->getUserById($order->user_id);
                (new WdXcxUserBalanceRecord())->addRecord($user, [
                    'change_price' => $order->pay_price,
                    'order_id' => $order_id.'TK',
                    'message' => '取消点餐订单返回',
                    'user_id' => $user->id,
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_CATERING_CANCEL
                ], WdXcxUserBalanceRecord::BALANCE_CHANGE_ADD);
            }
            //修改分销订单状态
            (new DistributionService(app()))->changeFxOrderStatus($order->order_id, -1);
        }catch (Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
    }

}