<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\common\model\coupon\WdXcxCoupon;
use think\model\concern\SoftDelete;

class WdXcxRechargePackage extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_recharge_package';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'coupon_con' => 'array',
    ];

    public function getCouponConShowAttr($value)
    {
        $coupon_con = $this->coupon_con;
        foreach ($coupon_con as $k => $item){
            $coupon_con[$k]['title'] = WdXcxCoupon::where('id', $item['coupon_id'])->value('title');
        }
        return $coupon_con;
    }

    public function getRechargeSendAttr()
    {
        $show_str = [];
        foreach ($this->CouponConShow as $k => $item){
            $show_str[] = $item['title'].'*'.$item['coupon_num'].'张';
        }
        if($this->get_gamecoin > 0){
            $show_str[] = '赠送'.$this->get_gamecoin.'游戏币';
        }
        if($this->getmoney > 0){
            $show_str[] = '赠送'.$this->getmoney.'元零钱';
        }
        if($this->getscore > 0){
            $show_str[] = '赠送'.$this->getscore.'积分';
        }
        return implode('、', $show_str);
    }

    /**获取充值套餐信息
     * @param $id
     * @return WdXcxRechargePackage|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRechargeInfoById($id)
    {
        $info = $this->where([
            'uniacid' => $this->uniacid,
            'id' => $id
        ])->find();
        if(!$info){
            throwError('指定充值套餐不存在');
        }
        return $info;
    }

}