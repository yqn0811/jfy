<?php

namespace app\api\controller;

use app\common\service\user\AccountService;
use think\App;

class AccountApiController extends ApiBaseController
{
    private $account_service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->account_service = new AccountService($app);
    }

    /**获取用户授权信息
     * @return void
     */
    public function getUserAuthInfo()
    {
        $params = $this->request->getMore([
            ['code', ''],
            ['link', ''],
        ]);
        $result = $this->account_service->getUserAuthInfo($params);
        $this->result($result);
    }

    public function createUserOrder()
    {
        $param = $this->request->postMore([
            ['grade', 1],
            ['pay_price', '0'],
            ['buy_time', '1'],
            ['timestamp', ''],
            ['sign', ''],
        ]);
        if(!in_array($param['buy_time'], [1,2,3])){
            throwError('请选择正确的购买时长');
        }
        $result = $this->account_service->createUserByVipgradeOrder($param);
        $this->result([
            'data' => $result
        ], 0, '下单成功');
    }

    public function initAccountConfig()
    {
        $param = $this->request->postMore([
            ['link', ''],
        ]);
        $result = $this->account_service->initAccount($param);
        $this->result($result);
    }
}