<?php

namespace app\index\controller;

use app\common\model\coupon\WdXcxCoupon;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxVipgrade;
use app\common\service\distribution\DistributionService;
use think\App;
use think\facade\View;

class DistributionController extends IndexBaseController
{
    private $distribution_service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->distribution_service = new DistributionService($app);
    }

    /**基础信息
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function base()
    {
        $this->checkUserRule(58);
        $base = $this->distribution_service->getBase();
        $coupon = WdXcxCoupon::where('uniacid', $this->uniacid)
            ->order('id desc')
            ->field('id, title')
            ->select();
        $coupon_count = 0;
        if($base){
            $coupon_count = count($base->coupon_info);
            $base = $base->toArray();
        }
        return View::fetch('distribution/base', [
            'base' => $base,
            'coupon' => $coupon,
            'coupon_count' => $coupon_count,
        ]);
    }

    /**基础信息保存
     * @return void
     */
    public function saveBase()
    {
        $this->checkUserRule(58);
        $param = $this->request->param();
        $this->distribution_service->saveBase($param);
        $this->success('保存成功');
    }

    /**分销商列表
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function lists()
    {
        $this->checkMenuPath(57, 59);
        $this->checkUserRule(59);
        $id = $this->uniacid;
        $grade = WdXcxVipgrade::where('uniacid', $id)
            ->field('grade_level, grade_name')
            ->select()
            ->toArray();
        $vip = input('vip');
        $key = input('user_info');
        $user_id = input('user_id');

        $user = WdXcxUser::where('uniacid', $id)
            ->where('leaguer_id', '<>', '')
            ->where(function ($query)use($vip, $key, $user_id){
                if($vip && $vip != 'all'){
                    $query->where('vip_grade', $vip);
                }
                if($key){
                    $query->where(function ($query)use($key){
                        $query->whereLike('nickname', '%'.$key.'%')->whereOr('nickname', 'like', '%'.$key.'%')->whereOr('mobile', 'like', '%'.$key.'%');
                    });
                }
                if($user_id){
                    $query->where('id', $user_id);
                }
            })->order('join_time desc, id desc')
            ->paginate([
                'list_rows' => 10,
                'query' => request()->param()
            ]);
        return View::fetch('distribution/lists', [
            'user' => $user,
            'grade_arr' => $grade,
            'vip' => $vip,
            'user_lable_id' => ''
        ]);
    }

    /**改变用户分销状态
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeDistributioStatus()
    {
        $uniacid = input('uniacid');
        $user_id = input('user_id');
        $user = WdXcxUser::where('uniacid', $uniacid)->where('id', $user_id)->find();
        if(!$user){
            $this->error('用户不存在');
        }
        $user->distribution_status = $user->distribution_status == 1 ? 0 : 1;
        $user->save();
        $this->success('修改成功');
    }

    /**分销订单列表
     * @return string
     */
    public function orders()
    {
        $key = input('key') ? input('key') : '';
        $start_get = input('start_get') ? input('start_get') : '';
        $end_get = input('end_get') ? input('end_get') : '';
        $orderform_type = input('orderform_type') ? input('orderform_type') : '';
        $status = input('status') ? input('status') : 100;
        list($lists, $total_money) = $this->distribution_service->getDistributionLists(request()->param());

        return View::fetch('distribution/orders', [
            'lists' => $lists,
            'key' => $key,
            'start_get' => $start_get,
            'end_get' => $end_get,
            'orderform_type' => $orderform_type,
            'status' => $status,
            'total_money' => $total_money
        ]);
    }

    /**提现申请列表
     * @return string
     */
    public function commission()
    {
        $key = input('key') ? input('key') : '';
        $start_get = input('start_get') ? input('start_get') : '';
        $end_get = input('end_get') ? input('end_get') : '';
        $status = input('status') ? input('status') : 100;
        $lists = $this->distribution_service->getUserWithdrawalLists(request()->param());
        return View::fetch('distribution/commission', [
            'lists' => $lists,
            'key' => $key,
            'start_get' => $start_get,
            'end_get' => $end_get,
            'status' => $status,
        ]);
    }

    /**审核提现申请
     * @return void
     */
    public function checkStatus()
    {
        $this->distribution_service->checkWithdrawalStatus(request()->param());
        $this->success('操作成功');
    }


}