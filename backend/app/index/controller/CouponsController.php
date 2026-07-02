<?php

namespace app\index\controller;

use app\common\model\album\WdXcxEvaluateRecords;
use app\common\model\coupon\WdXcxCoupon;
use app\common\model\coupon\WdXcxUserCoupon;
use app\common\model\user\WdXcxUser;
use app\common\service\album\AlbumService;
use app\common\service\coupon\UserCouponService;
use think\facade\View;

class CouponsController extends IndexBaseController
{
    /**卡券列表
     * @return string
     */
    public function coupons()
    {
        $this->checkMenuPath(16, 17);
        $this->checkUserRule(17);

        $lists = WdXcxEvaluateRecords::where('id', '>', 0)
            ->order('id desc')
            ->paginate([
            'list_rows' => 10,
            'query' => input(),
        ]);
        return View::fetch('coupons/coupons', [
            'coupon' => $lists,
        ]);
    }


    /**删除卡券
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteCoupons()
    {
        $this->checkUserRule(17);
        $couponid = input('couponid');
        if($couponid){
            $couponinfo = WdXcxEvaluateRecords::where('id', $couponid)->find();
            if(!$couponinfo){
                $this->error('指定内容不存在');
            }
            $couponinfo->delete();
            $this->success('删除成功', Url('CouponsController/coupons'));
        }else{
            $this->error('选择需要删除的卡券');
        }
    }

    public function addEvaluate()
    {
        $cate = (new AlbumService($this->app))->getEvaluateCateLists();
        return View::fetch('coupons/add_coupon', [
            'coupon' => '',
            'cate' => $cate,
        ]);
    }

    public function saveCoupon()
    {
        $this->checkUserRule(17);
        $params = input();
        if(empty($params['nickname']) || empty($params['evaluate_content']) || empty($params['purpose'])){
            $this->error('请填写完整的信息');
        }
        $evaluate = new WdXcxEvaluateRecords();
        $evaluate->evaluate_content = $params['evaluate_content'];
        $evaluate->purpose = $params['purpose'];
        $evaluate->nickname = $params['nickname'];
        $evaluate->avatar = $params['commonuploadpic1'];
        if(!empty($params['edittime'])){
            $evaluate->create_time = strtotime($params['edittime']);
        }
        $evaluate->save();
        $this->success('添加成功', Url('CouponsController/coupons'));
    }


}