<?php

namespace app\index\controller;

use app\common\model\coupon\WdXcxCoupon;
use app\common\model\user\WdXcxRechargePackage;
use think\facade\View;

class RechargeController extends IndexBaseController
{
    /**充值套餐列表
     * @return string
     */
    public function aggregate()
    {
        $this->checkMenuPath(26, 27);
        $this->checkUserRule(27);
        $uniacid = $this->uniacid;
        $lists = WdXcxRechargePackage::where('uniacid', $uniacid)
            ->order('id desc')
            ->paginate([
            'list_rows' => 10,
            'query' => input(),
        ]);
        return View::fetch('recharge/aggregate', [
            'guiz' => $lists,
        ]);
    }

    /**添加套餐
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addAggregate()
    {
        $this->checkUserRule(27);
        $uniacid = $this->uniacid;
        $cz = input('cz') ? input('cz') : 0;
        $recharge = null;
        if($cz){
            $recharge = WdXcxRechargePackage::where('id', $cz)->find();
            if(!$recharge){
                $this->error('套餐不存在');
            }
        }
        $coupon = WdXcxCoupon::where('uniacid', $uniacid)
            ->order('id desc')
            ->field('id, title')
            ->select();
        return View::fetch('recharge/add_aggregate', [
            'cz' => $cz,
            'recharge' => $recharge,
            'yhqs' => $coupon,
        ]);
    }

    /**保存套餐
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveAggregate()
    {
        $this->checkUserRule(27);
        $uniacid = $this->uniacid;
        $cz = input("cz");
        $data['title'] = input('title');
        $data['money'] = input("money") ? input("money") : 0;
        if(!$data['title'] || !$data['money']){
            $this->error('参数不完整');
        }
        $data['getmoney'] = input("getmoney") ? input("getmoney") : 0;
        $data['getscore'] = input("getscore") ? input("getscore") : 0;
        $data['get_gamecoin'] = input("get_gamecoin") ? input("get_gamecoin") : 0;
        $data['uniacid'] = $uniacid;
        $coupon_con = [];
        if(!empty(input('coupon_id/a'))){
            $coupon_id_arr = input('coupon_id/a');
            $coupon_num_arr = input('coupon_num/a');
            $j = 0;
            foreach ($coupon_id_arr as $k => $v) {
                if($v != 0 && $coupon_num_arr[$k] >0){
                    $coupon_con[$j]['coupon_id'] = $v;
                    $coupon_con[$j]['coupon_num'] = $coupon_num_arr[$k];
                    $j++;
                }
            }
        }
        $data['coupon_con'] = $coupon_con;
        if($cz){
            $info = WdXcxRechargePackage::where('uniacid', $uniacid)->where('id', $cz)->find();
            if(!$info){
                $this->error('指定套餐不存在');
            }
        }else{
            $info = new WdXcxRechargePackage();
        }
        $info->save($data);
        $this->success('操作成功', Url('RechargeController/aggregate'));
    }

    /**删除套餐
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteAggregate()
    {
        $this->checkUserRule(27);
        $uniacid = $this->uniacid;
        $cz = input('cz');
        if($cz){
            $info = WdXcxRechargePackage::where('uniacid', $uniacid)->where('id', $cz)->find();
            if(!$info){
                $this->error('指定套餐不存在');
            }
            $info->delete();
            $this->success('删除成功', Url('RechargeController/aggregate'));
        }else{
            $this->error('选择需要删除的套餐');
        }
    }
}