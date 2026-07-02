<?php

namespace app\api\controller;

use app\BaseController;
use app\common\service\pay\PayNotifyService;
use think\App;
use think\facade\Log;

class PayNotifyController extends BaseController
{
    private $notify_service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->notify_service = new PayNotifyService();
    }

    /**订单回调通知
     * @return string
     */
    public function orderNotify()
    {
        $this->notify_service->notify();
//        return json_encode([
//            'code' => 'success',
//            'msg' => '成功'
//        ], JSON_UNESCAPED_UNICODE);
//        return '{"code":"success","msg":"成功"}';
    }
}