<?php

namespace app\api\controller;

use app\common\service\catering\CateringService;
use think\App;

class CateringApiController extends ApiController
{
    private $catering_service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->catering_service = new CateringService($app);
    }

    /**获取点餐分类
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCateringCate()
    {
        $this->result($this->catering_service->getCateringCateLists());
    }

    /**获取菜品
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getCateringDishes()
    {
        $param = $this->request->getMore([
            ['cate_id', 0],
            ['page', 1],
        ]);
        $this->result($this->catering_service->getCateringDishes($param));
    }

    /**菜品详情
     * @return void
     */
    public function getCateringDishesDetail()
    {
        $param = $this->request->getMore([
            ['dishes_id', 0],
        ]);
        $this->result($this->catering_service->getCateringDishesDetail($param));
    }

    /**创建点餐订单
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createCateringDishesOrder()
    {
        $param = $this->request->postMore([
            ['dishes', ''],
            ['mobile', ''],
            ['pay_price', 0],
            ['take_time', 0],
            ['table_num', 0],
            ['remark', ''],
            ['pay_type', 0],
            ['user_coupon_id', 0],
        ]);
        $order_id = $this->catering_service->createCateringDishesOrder($param, request()->userID());
        $this->result(['order_id' => $order_id], 0, '下单成功');
    }

    /**点餐订单余额支付
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cateringDishesOrderPay()
    {
        $param = $this->request->postMore([
            ['order_id', ''],
        ]);
        $this->catering_service->cateringDishesOrderPay($param, request()->userID());
        $this->result([], '', 'success');
    }

    /**获取订单微信支付信息
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function getCateringDishesOrderWxPay()
    {
        $param = $this->request->postMore([
            ['order_id', ''],
        ]);
        $this->result($this->catering_service->getCateringDishesOrderWxPay($param, request()->userOpenid()));
    }

    /**获取购物车菜品价格
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException'
     */
    public function getCateringDishesCartPrice()
    {
        $param = $this->request->postMore([
            ['dishes', ''],
        ]);
        $this->result($this->catering_service->getCateringDishesCartPrice($param, request()->userID()));
    }

    /**获取用户点餐订单列表
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserCateringOrderLists()
    {
        $param = $this->request->postMore([
            ['page', 1],
            ['type', 0],
        ]);
        $this->result($this->catering_service->getUserCateringOrderLists($param, request()->userID()));
    }

    /**获取订单详情
     * @return void
     */
    public function getUserCateringOrderDetail()
    {
        $param = $this->request->postMore([
            ['order_id', ''],
        ]);
        $this->result($this->catering_service->getUserCateringOrderDetail($param, request()->userID()));
    }



}