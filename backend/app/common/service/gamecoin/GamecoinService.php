<?php

namespace app\common\service\gamecoin;

use app\common\model\coupon\WdXcxUserCoupon;
use app\common\model\gamecoin\WdXcxGamecoinPackage;
use app\common\model\order\WdXcxUserGamecoinOrderLists;
use app\common\model\order\WdXcxUserOrderLists;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserBalanceRecord;
use app\common\model\user\WdXcxUserGiveBalanceRecord;
use app\common\model\user\WdXcxUserWxpayRecord;
use app\common\service\BaseService;
use app\common\service\distribution\DistributionService;
use app\common\service\pay\PayService;
use app\common\service\ylb\YlbApiService;
use think\App;
use think\cache\driver\Redis;
use think\Exception;
use think\facade\Db;

class GamecoinService extends BaseService
{
    private $package_model;
    private $order_model;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->package_model = new WdXcxGamecoinPackage();
        $this->order_model = new WdXcxUserGamecoinOrderLists();
    }

    /**获取套餐列表
     * @param $param
     * @param $type
     * @return WdXcxGamecoinPackage[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPackageLists($param, $type=1)
    {
        $lists_sql = $this->package_model->where('uniacid', $this->uniacid)
            ->where(function ($query)use($param){

            })->order('sort_num', 'desc')
            ->order('id', 'desc');
        if($type == 1){
            $lists = $lists_sql->paginate([
                'list_rows' => 10,
                'query' => input(),
            ]);
        }else{
            $lists = $lists_sql->field('id, get_gamecoin, pay_money')
            ->select();
        }
        return $lists;
    }

    /**保存套餐
     * @param $param
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function saveGamecoinPackageInfo($param)
    {
        $p_id = empty($param['p_id']) ? 0 : $param['p_id'];
        if(empty($param['get_gamecoin'])){
            throwError('请输入游戏币数量');
        }
        if($p_id){
            $info = $this->getPackageInfoById($p_id);
        }else{
            $info = new WdXcxGamecoinPackage();
            $info->uniacid = $this->uniacid;
        }
        $info->sort_num = empty($param['sort_num']) ? 0 : $param['sort_num'];
        $info->get_gamecoin = $param['get_gamecoin'];
        $info->pay_money = empty($param['pay_money']) ? 0 : $param['pay_money'];
        $info->save();
    }

    /**获取指定套餐信息
     * @param $pid
     * @return WdXcxGamecoinPackage|array|mixed|\think\Model|null
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPackageInfoById($pid)
    {
        $info = $this->package_model->find($pid);
        if(!$info){
            throwError('套餐不存在');
        }
        return $info;
    }

    /**订单列表
     * @param $param
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getOrdersLists($param)
    {
        $lists = $this->order_model->where('uniacid', $this->uniacid)
            ->where(function ($query)use($param){
                if(!empty($param['user_id'])){
                    $query->where('user_id', $param['user_id']);
                }
                if(!empty($param['start_get'])){
                    $query->where('create_time', '>=', strtotime($param['start_get']));
                }
                if(!empty($param['end_get'])){
                    $query->where('create_time', '<=', strtotime($param['end_get']));
                }
                if(!empty($param['key'])){
                    $query->where(function ($query)use($param){
                        $query->whereIn('user_id', $param['suids']);
                        $query->whereOr('order_id', 'like', '%'.$param['key'].'%');
                    });
                }
                if(isset($param['pay_type']) && $param['pay_type'] != -1){
                    $query->where('pay_type', $param['pay_type']);
                }
            })->order('id desc')
            ->paginate([
                'list_rows' => 10,
                'query' => input(),
            ]);
        $total_money = 0;
        $total_get = 0;
        if(!empty($param['start_get']) || !empty($param['end_get'])){
            $total_money = $this->order_model->where('uniacid', $this->uniacid)
                ->where(function ($query)use($param){
                    if(!empty($param['user_id'])){
                        $query->where('user_id', $param['user_id']);
                    }
                    if(!empty($param['start_get'])){
                        $query->where('create_time', '>=', strtotime($param['start_get']));
                    }
                    if(!empty($param['end_get'])){
                        $query->where('create_time', '<=', strtotime($param['end_get']));
                    }
                    if(!empty($param['key'])){
                        $query->where(function ($query)use($param){
                            $query->whereIn('user_id', $param['suids']);
                            $query->whereOr('order_id', 'like', '%'.$param['key'].'%');
                        });
                    }
                    if(isset($param['pay_type']) && $param['pay_type'] != -1){
                        $query->where('pay_type', $param['pay_type']);
                    }
                })->sum('pay_price');
            $total_get = $this->order_model->where('uniacid', $this->uniacid)
                ->where(function ($query)use($param){
                    if(!empty($param['user_id'])){
                        $query->where('user_id', $param['user_id']);
                    }
                    if(!empty($param['start_get'])){
                        $query->where('create_time', '>=', strtotime($param['start_get']));
                    }
                    if(!empty($param['end_get'])){
                        $query->where('create_time', '<=', strtotime($param['end_get']));
                    }
                    if(!empty($param['key'])){
                        $query->where(function ($query)use($param){
                            $query->whereIn('user_id', $param['suids']);
                            $query->whereOr('order_id', 'like', '%'.$param['key'].'%');
                        });
                    }
                    if(isset($param['pay_type']) && $param['pay_type'] != -1){
                        $query->where('pay_type', $param['pay_type']);
                    }
                })->sum('get_gamecoin');
        }
        return [$lists, $total_money, $total_get];
    }

    /**创建购买订单
     * @param $param
     * @param $user_id
     * @return array|null
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createPackageOrder($param, $user_id)
    {
        if(empty($param['pid'])){
            throwError('请选择套餐');
        }
        $user = (new WdXcxUser())->getUserById($user_id);
        $info = $this->getPackageInfoById($param['pid']);
        $param = $this->checkUserBy($info, $user, $param);
        return $this->createOrder($user, $info, $param);

    }

    /**创建订单
     * @param $user
     * @param $info
     * @param $param
     * @return array|void
     * @throws \cores\exception\BaseException
     */
    private function createOrder($user, $info, $param)
    {
        $pay_type = isset($param['pay_type']) ? $param['pay_type'] : 0;
        $order_id = generateOrderId('G');
        $order_data = [
            'uniacid' => $this->uniacid,
            'user_id' => $user->id,
            'order_id' => $order_id,
            'package_id' => $info->id,
            'pay_price' => $param['price'],
            'get_gamecoin' => $info->get_gamecoin,
            'status' => 2,
            'pay_info' => $info->toArray(),
            'pay_type' => $pay_type,
            'user_coupon_id' => $param['user_coupon_id'],
            'coupon_minus_money' => $param['coupon_minus_money'],
        ];
        $user_order_data = [
            'uniacid' => $this->uniacid,
            'user_id' => $user->id,
            'user_mobile' => $user->mobile,
            'order_id' => $order_id,
            'orderform_type' => WdXcxUserOrderLists::ORDER_TYPE_GAMECOIN,
            'status' => 2,
        ];
        if($pay_type == 1){
            $pay_info = (new PayService($this->app))->getPayInfo([
                'order_id' => $order_id,
                'pay_price' => $order_data['pay_price'],
                'subject' => '购买游戏币订单',
                'openid' => $user->openid,
            ]);
            $redis = new Redis(GetRedisConf());
            $redis->set('gamecoin_order_'.$order_id, json_encode(compact('order_data', 'user_order_data', 'info'), JSON_UNESCAPED_UNICODE), 600);
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
            $this->saveGamecoinOrderData($order_id, $param['price'], $info->get_gamecoin, $user, $pay_type, $order_data, $user_order_data);
            return ['msg' => '下单成功', 'pay_type' => $pay_type, 'order_id' => $order_id];
        }
    }

    /**订单支持成功回调
     * @param $notify_order_data
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveGamecoinOrder($notify_order_data)
    {
        $order_data = $notify_order_data['order_data'];
        $user_order_data = $notify_order_data['user_order_data'];
        $info = $notify_order_data['info'];
        $user = (new WdXcxUser())->getUserById($user_order_data['user_id']);
        $this->saveGamecoinOrderData($order_data['order_id'], $order_data['pay_price'], $info['get_gamecoin'], $user, 1, $order_data, $user_order_data);
    }

    /**保存订单数据
     * @param $order_id
     * @param $price
     * @param $get_gamecoin
     * @param $user
     * @param $pay_type
     * @param $order_data
     * @param $user_order_data
     * @return void
     * @throws \cores\exception\BaseException
     */
    private function saveGamecoinOrderData($order_id, $price, $get_gamecoin, $user, $pay_type, $order_data, $user_order_data)
    {
        Db::startTrans();
        try {
            $buy_summary = '余额购买';
            if($pay_type == 2){
                //零钱流水，扣除余额
                (new WdXcxUserGiveBalanceRecord())->addRecord($user, [
                    'change_price' => $price,
                    'order_id' => $order_id,
                    'message' => '购买游戏币',
                    'user_id' => $user->id,
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_GAMECOIN
                ]);
                $buy_summary = '零钱购买';
            }else if($pay_type == 0){
                //消费流水，扣除余额
                (new WdXcxUserBalanceRecord())->addRecord($user, [
                    'change_price' => $price,
                    'order_id' => $order_id,
                    'message' => '购买游戏币',
                    'user_id' => $user->id,
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_GAMECOIN
                ]);
            }else{
                $buy_summary = '微信支付购买';
                //保存用户微信消费记录
                WdXcxUserWxpayRecord::create([
                    'uniacid' => $order_data['uniacid'],
                    'user_id' => $order_data['user_id'],
                    'order_id' => $order_id,
                    'pay_price' => $order_data['pay_price'],
                    'create_time' => time(),
                    'order_type' => WdXcxUserWxpayRecord::WX_PAY_GAMECOIN_ORDER
                ]);
            }
            //增加游戏币
            (new YlbApiService())->changeUserValues($user->leaguer_id, 402, $get_gamecoin, $buy_summary);
            $order_model = new WdXcxUserGamecoinOrderLists();
            $order_model->save($order_data);
            $user_order_data['orderform_id'] = $order_model->id;
            WdXcxUserOrderLists::create($user_order_data);
            //使用优惠券
            if(!empty($order_data['user_coupon_id'])){
                (new WdXcxUserCoupon())->useUserCoupon($this->uniacid, $order_data['user_id'], $order_data['user_coupon_id'], WdXcxUserCoupon::USE_COUPON_GAMECOIN);
            }
            //游戏币分销订单
            if($pay_type == 1){
                (new DistributionService(app()))->createFxOrder($user, $order_id, $order_model->id, WdXcxUserOrderLists::ORDER_TYPE_GAMECOIN, $order_data['pay_price']);
            }
        }catch (Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
    }

    /**检查购买条件参数
     * @param $info
     * @param $user
     * @param $param
     * @return mixed
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function checkUserBy($info, $user, $param)
    {
        $need_pay_price = $info->pay_money;
        $coupon_minus_money = 0;
        if($param['user_coupon_id']){
            list($need_pay_price, $coupon_minus_money) = (new WdXcxUserCoupon())->checkOrderUseCoupon(request()->userID(), $param['user_coupon_id'], $need_pay_price);
        }
        $param['coupon_minus_money'] = $coupon_minus_money;
        if(bccomp($param['price'], $need_pay_price, 2) !== 0){
            throwError('金额异常');
        }
        $pay_type = isset($param['pay_type']) ? $param['pay_type'] : 0;
        if($pay_type == 2){
            if(bccomp($user->give_balance, $param['price'], 2) == -1){
                throwError('用户零钱不足');
            }
        }elseif($pay_type == 0){
            $UserBalance = str_replace(',', '', $user->UserBalance);
            if(bccomp($UserBalance, $param['price'], 2) == -1){
                throwError('用户余额不足');
            }
        }
        return $param;
    }


}