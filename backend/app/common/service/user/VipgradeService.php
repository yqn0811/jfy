<?php

namespace app\common\service\user;

use app\common\model\order\WdXcxUserBuyGradeOrderLists;
use app\common\model\order\WdXcxUserOrderLists;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserBalanceRecord;
use app\common\model\user\WdXcxUserIntegralRecord;
use app\common\model\user\WdXcxVipgrade;
use app\common\service\BaseService;
use app\common\service\pay\PayService;
use think\App;
use think\Exception;
use think\facade\Db;

class VipgradeService extends BaseService
{
    private $vipgrade_model;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->vipgrade_model = new WdXcxVipgrade();
    }

    /**会员等级列表
     * @return WdXcxVipgrade[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getVipgradeLists()
    {
        $lists = $this->vipgrade_model->where('uniacid', $this->uniacid)
            ->field('grade_level, grade_name, annual_fee, market_annual_fee, midd_month_fee, market_midd_month_fee,
                month_fee, market_month_fee, new_buy_annual, cloud_size, editor_number, upload_size_type, upload_size')
            ->order('grade_level asc')
            ->select()->each(function ($item){
                $item->show_annual_del_str = $item->ShowAnnualDelStr;
                $item->show_month_del_str = $item->ShowMonethDelStr;
                $item->cloud_size_str = $item->CloudSizeStr;
                $item->upload_size_value = $item->UploadSizeValue;
            });
        return $lists;
    }

    /**创建购买会员订单
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createUserByVipgradeOrder($param, $user_id)
    {
        $user = (new WdXcxUser())->getUserById($user_id);
        $grade_info = $this->vipgrade_model->where([
            'uniacid' => $this->uniacid,
            'grade_level' => $param['grade']
        ])->find();
        if(!$grade_info){
            throwError('指定会员等级不存在');
        }
        $this->checkByGrade($user, $grade_info, $param);
        return $this->createUserByVipgradeOrderHandler($user, $grade_info, $param);
    }

    /**购买会员等级订单数据
     * @param $user
     * @param $grade_info
     * @return void
     * @throws \cores\exception\BaseException
     */
    private function createUserByVipgradeOrderHandler($user, $grade_info, $param)
    {
        $order_id = generateOrderId('V');
        Db::startTrans();
        try {
            $pay_info = (new PayService($this->app))->getPayInfo([
                'order_id' => $order_id,
                'pay_price' => $param['pay_price']*100,
                'body' => '购买会员',
                'openid' => request()->userOpenid(),
            ]);
            $buy_grade_order = new WdXcxUserBuyGradeOrderLists();
            $buy_grade_order->save([
                'uniacid' => $this->uniacid,
                'user_id' => $user->id,
                'order_id' => $order_id,
                'grade_level' => $grade_info->grade_level,
                'pay_price' => $param['pay_price'],
                'buy_day_limit' => $param['buy_time'],
                'grade_info' => json_encode($grade_info->toArray()),
                'status' => 1,
                'pay_info' => [
                    'timeStamp' => $pay_info['timeStamp'],
                    'nonceStr' => $pay_info['nonceStr'],
                    'package' => $pay_info['package'],
                    'signType' => $pay_info['signType'],
                    'paySign' => $pay_info['paySign'],
                ],
            ]);

//            WdXcxUserOrderLists::create([
//                'uniacid' => $this->uniacid,
//                'user_id' => $user->id,
//                'user_mobile' => $user->mobile ? $user->mobile : '',
//                'order_id' => $order_id,
//                'orderform_id' => $buy_grade_order->id,
//                'orderform_type' => WdXcxUserOrderLists::ORDER_TYPE_BUY_GRADE,
//                'status' => 2,
//            ]);
//            (new WdXcxUserBalanceRecord())->addRecord($user, [
//                'order_id' => $order_id,
//                'change_price' => $grade_info->buy_price,
//                'message' => '购买会员等级',
//                'user_id' => $user->id,
//                'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_VIPGRADE
//            ]);
//            $end_time = strtotime(date('Y-m-d', time()) . ' 23:59:59') + $grade_info->buy_day_limit * 86400;
//            $user->changeUserVipGrade($user, $grade_info->grade_level, $end_time, '购买会员等级【'.$grade_info->grade_name.'】');
        }catch (Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
        return [
            'pay_info' => $buy_grade_order['pay_info'],
            'order_id' => $order_id
        ];
    }

    /**检查是否能购买
     * @param $user
     * @param $grade_info
     * @param $param
     * @return void
     * @throws \cores\exception\BaseException
     */
    private function checkByGrade($user, $grade_info, $param)
    {
        if($user->vip_grade >= $grade_info->grade_level){
            throwError('用户会员等级不能高于购买等级');
        }
//        if($grade_info->buy_grade != 1){
//            throwError('该会员等级不允许购买');
//        }
        if($param['buy_time'] == 1){
            $need_pay = $grade_info->annual_fee;
            //检查是否有为新客
            $pay_order = WdXcxUserBuyGradeOrderLists::where([
                'user_id' => $user->id,
                'status' => 2
            ])->find();
            if(!$pay_order){
                $need_pay = bcsub($grade_info->annual_fee, $grade_info->new_buy_annual, 2);
            }
        }
        if($param['buy_time'] == 2){
            $need_pay = $grade_info->midd_month_fee;
        }
        if($param['buy_time'] == 3){
            $need_pay = $grade_info->month_fee;
        }
        if(bccomp($need_pay, $param['pay_price'], 2) != 0){
            throwError('购买价格有误');
        }
//        $UserBalance = str_replace(',', '', $user->UserBalance);
//        if(bccomp($UserBalance, $grade_info->buy_price, 2) == -1){
//            throwError('当前余额不足');
//        }
    }
}