<?php

namespace app\api\controller;

use app\common\service\bridge\JiafangyunWebPaymentService;
use think\App;

class WebPaymentApiController extends ApiBaseController
{
    private $webPaymentService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->webPaymentService = new JiafangyunWebPaymentService($app);
    }

    public function getSubscriptionPlans()
    {
        $this->result($this->webPaymentService->getSubscriptionPlans());
    }

    public function getRechargePlans()
    {
        $this->result($this->webPaymentService->getPointsRechargePlans());
    }

    public function createMembershipOrder()
    {
        $param = $this->request->postMore([
            ['membership_plan_id', 0],
            ['plan_id', 0],
            ['grade', 0],
            ['buy_time', 0],
            ['package_type', ''],
            ['coupon_code', ''],
        ]);
        $this->result($this->webPaymentService->createMembershipOrder(request()->userID(), $param), 0, '下单成功');
    }

    public function createRechargeOrder()
    {
        $param = $this->request->postMore([
            ['points_plan_id', 0],
            ['plan_id', 0],
            ['rid', 0],
            ['coupon_code', ''],
        ]);
        $this->result($this->webPaymentService->createRechargeOrder(request()->userID(), $param), 0, '下单成功');
    }

    public function getOrderStatus()
    {
        $param = $this->request->getMore([
            ['order_no', ''],
            ['order_id', ''],
        ]);
        $orderNo = $param['order_no'] ?: $param['order_id'];
        $this->result($this->webPaymentService->getOrderStatus(request()->userID(), $orderNo));
    }

    public function getOrderList()
    {
        $param = $this->request->getMore([
            ['page', 1],
            ['page_size', 20],
            ['pageSize', 20],
            ['status', ''],
            ['order_type', ''],
            ['type', ''],
            ['order_no', ''],
        ]);
        $this->result($this->webPaymentService->getOrderList(request()->userID(), $param));
    }
}
