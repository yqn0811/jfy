<?php

namespace app\api\controller;

use app\common\service\user\VipgradeService;
use think\App;

class VipgradeApiController extends ApiBaseController
{
    private $vipgrade_service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->vipgrade_service = new VipgradeService($app);
    }

    /**会员等级列表
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getVipgradeLists()
    {
        $this->result($this->vipgrade_service->getVipgradeLists());
    }

    /**用户购买会员等级
     * @return void
     */
    public function createUserByVipgradeOrder()
    {
        $param = $this->request->postMore([
            ['grade', 1],
            ['pay_price', '0'],
            ['buy_time', '1'],
        ]);
        if(!in_array($param['buy_time'], [1,2,3])){
            throwError('请选择正确的购买时长');
        }
        $result = $this->vipgrade_service->createUserByVipgradeOrder($param, request()->userID());
        $this->result([
            'data' => $result
        ], 0, '下单成功');
    }
}