<?php

namespace app\api\controller;

use app\common\service\distribution\DistributionService;
use think\App;

class DistributionApiController extends ApiBaseController
{
    private $distribution_service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->distribution_service = new DistributionService($app);
    }

    /**获取用户统计数据
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserDistributionData()
    {
        $param = $this->request->postMore([
            ['date_type', 0],
            ['date_start', ''],
            ['date_end', ''],
        ]);
        $this->result($this->distribution_service->getUserDistributionData($param, request()->userID()));
    }

    /**获取分销商下级列表
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserDistributionJunior()
    {
        $this->result($this->distribution_service->getUserDistributionJunior(request()->userID()));
    }

    /**获取分销商订单列表
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserDistributionOrderLists()
    {
        $param = $this->request->postMore([
            ['status', 0],
            ['page', 1],
        ]);
        $this->result($this->distribution_service->getUserDistributionOrderLists($param, request()->userID()));
    }

    /**获取提现页面基础信息
     * @return void
     */
    public function getUserDistributionWithdrawalInfo()
    {
        $this->result($this->distribution_service->getUserDistributionWithdrawalInfo(request()->userID()));
    }

    /**提现记录列表
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getUserDistributionWithdrawalLists()
    {
        $this->result($this->distribution_service->getUserDistributionWithdrawalLists(request()->userID()));
    }

    /**分销商提现申请
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserDistributionSubmitWithdrawal()
    {
        $param = $this->request->postMore([
            ['total_money', 0],
            ['true_name', ''],
        ]);
        $this->distribution_service->getUserDistributionSubmitWithdrawal($param, request()->userID());
        $this->result([], 0, '申请成功');
    }

    /**获取分销商二维码
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function getUserDistributionQrcode()
    {
        $this->result($this->distribution_service->getUserDistributionQrcode(request()->userID()));
    }
}