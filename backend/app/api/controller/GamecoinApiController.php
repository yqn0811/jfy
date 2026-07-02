<?php

namespace app\api\controller;

use app\common\service\gamecoin\GamecoinService;
use think\App;

class GamecoinApiController extends ApiBaseController
{
    private $gamecoin_service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->gamecoin_service = new GamecoinService($app);
    }

    /**获取套餐列表
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPackageLists()
    {
        $this->result($this->gamecoin_service->getPackageLists([], 2));
    }

    /**创建订单
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createPackageOrder()
    {
        $param = $this->request->postMore([
            ['pid', 0],
            ['price', ''],
            ['pay_type', 0],
            ['user_coupon_id', 0],
        ]);
        $result = $this->gamecoin_service->createPackageOrder($param, request()->userID());
        $this->result($result, 0, '下单成功');
    }
}