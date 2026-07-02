<?php

namespace app\api\controller;

use app\common\service\recharge\RechargeService;
use think\App;

class RechargeApiController extends ApiBaseController
{
    private $recharge_service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->recharge_service = new RechargeService($app);
    }

    public function getRechargeList()
    {
        $this->result($this->recharge_service->getRechargeList());
    }

    public function createRechargeOrder()
    {
        $param = $this->request->postMore([
            ['rid', 0],
            ['pay_price', '0'],
            ['invite_code', ''],
        ]);
        $result = $this->recharge_service->createRechargeOrder($param);
        $this->result($result, 0, '下单成功');
    }

    public function checkPayStatus()
    {
        $param = $this->request->postMore([
            ['order_id', ''],
        ]);
        $this->recharge_service->checkPayStatus($param['order_id'], request()->userID());
        $this->result([], 0, '支付成功');
    }
}
