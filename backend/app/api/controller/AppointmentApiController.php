<?php

namespace app\api\controller;

use app\common\service\appointment\AppointmentService;
use think\App;

class AppointmentApiController extends ApiBaseController
{
    private $appointment_service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->appointment_service = new AppointmentService($app);
    }

    /**获取预约分类列表
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAppointmentCate()
    {
        $this->result($this->appointment_service->getAppointmentCate());
    }

    /**获取预约列表
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAppointmentLists()
    {
        $param = $this->request->getMore([
            ['cate_id', 1],
            ['page', 1],
        ]);
        $this->result($this->appointment_service->getAppointmentCateList($param));
    }

    /**获取预约详情
     * @return void
     */
    public function getAppointmentContentInfo()
    {
        $param = $this->request->getMore([
            ['id', 0],
            ['choose_date', ''],
        ]);
        $this->result($this->appointment_service->getAppointmentContentInfo($param));
    }

    /**创建用户预约订单
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createUserAppointmentOrder()
    {
        $param = $this->request->postMore([
            ['appoint_id', 0],
            ['appoint_date', ''],
            ['appoint_time', ''],
            ['mobile', ''],
            ['pay_score', ''],
        ]);
        $this->appointment_service->createUserAppointmentOrder($param, request()->userID());
        $this->result([], 0, '下单成功');
    }

    /**获取用户预约订单列表
     * @return void
     */
    public function getUserAppointmentOrderLists()
    {
        $param = $this->request->postMore([
            ['type', 0],
            ['page', 1],
        ]);
        $this->result($this->appointment_service->getUserAppointmentOrderLists($param, request()->userID()));
    }

    /**获取订单核销二维码
     * @return void
     */
    public function getUserAppointmentOrderQrcode()
    {
        $param = $this->request->postMore([
            ['order_id', ''],
        ]);
        $this->result($this->appointment_service->getUserAppointmentOrderQrcode($param, request()->userID()));
    }
}