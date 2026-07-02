<?php

namespace app\common\model\coupon;

use app\common\model\BaseModel;
use app\common\model\user\WdXcxUser;
use app\common\service\ylb\YlbApiService;
use think\cache\driver\Redis;
use think\model\concern\SoftDelete;

class WdXcxUserCoupon extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_coupon';
    protected $autoWriteTimestamp = true;

    const USER_COUPON_BACK_BATCH = 'back_batch'; //后台批量发券
    const USER_COUPON_RECHARGE_SEND = 'recharge_send'; //充值赠送
    const USER_COUPON_NEW_USER = 'new_user'; //新用户赠送
    const USER_COUPON_FX_SCAN = 'fx_scan'; //分销绑定扫码赠送

    const USE_COUPON_RESTAURANT = 'restaurant'; //点餐使用
    const USE_COUPON_TICKETING = 'ticketing'; //套票使用
    const USE_COUPON_GAMECOIN = 'gamecoin'; //购买游戏币使用
    const USE_COUPON_BACK_HX = 'back_hx'; //后台核销使用

    public function getuserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    public function getUseTypeStrAttr()
    {
        $str = '';
        if($this->use_type){
            switch ($this->use_type){
                case self::USE_COUPON_RESTAURANT:
                    $str = '点餐订单消费';
                    break;
                case self::USE_COUPON_TICKETING:
                    $str = '套票订单消费';
                    break;
                case self::USE_COUPON_GAMECOIN:
                    $str = '游戏币订单消费';
                    break;
                case self::USE_COUPON_BACK_HX:
                    $str = '核销使用';
                    break;
            }
        }
        return $str;
    }

    public function getUseStatusAttr($value)
    {
        if($value == 1 && $this->etime != 0 && $this->etime < time()){
            $this->where('id', $this->id)->update(['use_status' => 3]);
            $value = 3;
        }
        if($value == 1 && $this->coupon_id == -1){
            $leaguerID = WdXcxUser::where('id', $this->user_id)->value('leaguer_id');
            //作废的券
            $lists = (new YlbApiService())->getUserCouponLists($leaguerID, 4);
            if(count($lists) > 0){
                $ids = array_column($lists, 'ID');
                if(in_array($this->remote_coupon_id, $ids)){
                    $this->where('id', $this->id)->update(['use_status' => 3]);
                    $value = 3;
                }
            }
            //已使用的券
            $has_used_lists = (new YlbApiService())->getUserCouponLists($leaguerID, 2);
            if(count($has_used_lists) > 0){
                $ids = array_column($has_used_lists, 'ID');
                if(in_array($this->remote_coupon_id, $ids)){
                    $this->where('id', $this->id)->update(['use_status' => 2, 'use_time' => time(), 'use_info' => '油菜花端使用']);
                    $value = 2;
                }
            }

        }
        return $value;
    }

    /**赠送用户卡券
     * @param $user
     * @param $coupon_info
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function giveUserCoupon($user, $coupon_info, $message='', $get_type='')
    {
        $coupon_id = [];
        foreach ($coupon_info as $item){
            $coupon = WdXcxCoupon::where('id', $item['coupon_id'])->find();
            if($coupon){
                $use_time = $coupon->CounponUseTime;
                for($i=0; $i<$item['coupon_num']; $i++){
                    $coupon_id[] = $this->insertGetId([
                        'uniacid' => $this->uniacid,
                        'user_id' => $user->id,
                        'coupon_id' => $coupon->id,
                        'coupon_title' => $coupon->title,
                        'coupon_type' => $coupon->coupon_type,
                        'bg_image' => $coupon->bg_image,
                        'btime' => $use_time['start_time'],
                        'etime' => $use_time['end_time'],
                        'message' => $message,
                        'use_rule' => $coupon->use_rule,
                        'pay_money' => $coupon->pay_money,
                        'minus_money' => $coupon->minus_money,
                        'get_type' => $get_type,
                        'create_time' => time(),
                        'update_time' => time(),
                    ]);
                }
            }
        }
        if(count($coupon_id) > 0){
            $redis = new Redis(GetRedisConf());
            $old_data = $redis->get('user_new_coupon_count_'.$user->id);
            if($old_data){
                $redis->set('user_new_coupon_count_'.$user->id, $old_data+count($coupon_id));
            }else{
                $redis->set('user_new_coupon_count_'.$user->id, count($coupon_id));
            }
        }
        return $coupon_id;
    }

    /**根据优惠券ID或者优惠券名称查询最新到期的优惠券
     * @param $user_id
     * @param $coupon_id
     * @param $coupon_title
     * @return int[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRecentlyDataCouponData($user_id, $coupon_id=0, $coupon_title='')
    {
        $btime = 0;
        $etime = 0;
        $coupon = $this->getRecentlyDataCoupon($user_id, true, $coupon_id, $coupon_title);
        if($coupon){
            $btime = $coupon->btime;
            $etime = $coupon->etime;
        }
        $btime_str = $btime ?  date('Y-m-d', $btime) : 0;
        $etime_str = $etime ?  date('Y-m-d', $etime) : 0;
        return [$btime_str, $etime_str, $coupon->id];
    }

    /**获取最新到期的优惠券
     * @param $user_id
     * @param $coupon_id
     * @param $coupon_title
     * @return WdXcxUserCoupon|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getRecentlyDataCoupon($user_id,$forever=false, $coupon_id=0, $coupon_title='')
    {
        $coupon = $this->where('user_id', $user_id)
            ->where(function ($query)use($coupon_id, $coupon_title, $forever){
                if($coupon_id){
                    $query->where('coupon_id', $coupon_id);
                }
                if($coupon_title){
                    $query->where('coupon_title', $coupon_title)->where('coupon_id', -1);
                }
                if(!$forever){
                    $query->where('etime', '>', 0);
                }
            })
            ->where('use_status', 1)
            ->order('etime asc')
            ->find();
        return $coupon;
    }

    public function checkCouponCanUse($coupon, $pay_price)
    {
        $can_use = 1; //1可用 0 不可用
        if($coupon->use_status != 1){
            return 0;
        }
        if(($coupon->btime > 0 && time() < $coupon->btime) || ($coupon->etime > 0 && time() > $coupon->etime)){
            return 0;
        }

        if(bccomp($coupon->pay_money, $pay_price, 2) == 1){
            return 0;
        }
        return $can_use;
    }

    /**检查订单是否可用优惠券
     * @param $user_id
     * @param $user_coupon_id
     * @param $pay_price
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkOrderUseCoupon($user_id, $user_coupon_id, $pay_price)
    {
        $coupon = $this->where('id', $user_coupon_id)->where('user_id', $user_id)->find();
        if(!$coupon){
            throwError('指定优惠券不存在');
        }
        if($coupon->use_status != 1){
            throwError('指定优惠券状态不正确');
        }
        if(($coupon->btime > 0 && time() < $coupon->btime) || ($coupon->etime > 0 && time() > $coupon->etime)){
            throwError('指定优惠券状态不在使用日期内');
        }
        if(bccomp($coupon->pay_money, $pay_price, 2) == 1){
            throwError('指定优惠券状态不满足使用条件');
        }
        $pay_price = bcsub($pay_price, $coupon->minus_money, 2);
        $minus_money = $coupon->minus_money;
        if($pay_price < 0){
            $pay_price = 0;
            $minus_money = bcsub($coupon->minus_money, $pay_price, 2);
        }
        return [$pay_price, $minus_money];
    }

    /**使用优惠券
     * @param $uniacid
     * @param $user_id
     * @param $user_coupon_id
     * @param $use_type
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function useUserCoupon($uniacid, $user_id, $user_coupon_id, $use_type)
    {
        $user_coupon = $this->where([
            'uniacid' => $uniacid,
            'user_id' => $user_id,
            'id' => $user_coupon_id
        ])->find();
        if($user_coupon){
            $user_coupon->use_status = 2;
            $user_coupon->use_time = time();
            $user_coupon->use_type = $use_type;
            $user_coupon->save();
        }
    }
}