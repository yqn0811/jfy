<?php

namespace app\api\controller;

use app\common\service\exchange\ExchangeService;
use think\App;

class ExchangeApiController extends ApiBaseController
{
    private $exchange_service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->exchange_service = new ExchangeService($app);
    }

    /**积分兑换列
     * @param $type 1 换余额  2 换积分 3 换实物
     * @return void
     */
    public function getGiftLists()
    {
        $this->result($this->exchange_service->getGiftLists());
    }

    /**获取实物兑换分类
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGiftCates()
    {
        $this->result($this->exchange_service->getGiftCates());
    }

    /**按分类获取实物列表
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getGiftCateGoods()
    {
        $param = $this->request->getMore([
            ['cate_id', ''],
        ]);
        $this->result($this->exchange_service->getGiftCateGoods($param));
    }

    /**获取积分兑换详情
     * @param $id
     * @return void
     */
    public function getGiftInfo($id)
    {
        $this->result($this->exchange_service->getGiftInfo($id));
    }

    /**创建用户兑换订单
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createUserExchangeOrder()
    {
        $param = $this->request->postMore([
            ['goods_id', ''],
        ]);
        $this->exchange_service->createExchangeOrder($param, request()->userID());
        $this->result([], 0, '下单成功');
    }

    /**获取兑换订单列表
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserExchangeOrderLists()
    {
        $param = $this->request->getMore([
            ['type', 0],
        ]);
        $this->result($this->exchange_service->getUserExchangeOrderLists($param, request()->userID()));
    }

    /**获取订单核销二维码
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserExchangeOrderQrcode()
    {
        $param = $this->request->postMore([
            ['order_id', ''],
        ]);
        $this->result($this->exchange_service->getUserExchangeOrderQrcode($param, request()->userID()));
    }
}