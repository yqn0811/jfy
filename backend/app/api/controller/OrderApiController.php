<?php

namespace app\api\controller;

use app\common\model\order\WdXcxUserOrderLists;
use app\common\service\order\OrderService;
use think\App;

class OrderApiController extends ApiBaseController
{
    private $order_service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->order_service = new OrderService($app);
    }

    public function getOrderPayInfo()
    {
        $order = WdXcxUserOrderLists::with('orderform')
            ->where('uniacid', $this->uniacid)
            ->order('id desc')
            ->paginate(10);
        $this->result($order);

//        $this->order_service->getPayInfo();
    }

    /**获取用户所有订单的列表
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserAllOrderLists()
    {
        $param = $this->request->getMore([
            ['type', 0],
            ['page', 1],
        ]);
        $this->result($this->order_service->getUserAllOrderLists($param, request()->userID()));
    }
}