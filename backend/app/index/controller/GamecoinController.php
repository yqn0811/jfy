<?php

namespace app\index\controller;

use app\common\model\user\WdXcxUser;
use app\common\service\gamecoin\GamecoinService;
use think\App;
use think\facade\View;

class GamecoinController extends IndexBaseController
{
    private $gamecoin_service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->gamecoin_service = new GamecoinService($app);
    }

    /**套餐管理
     * @return string
     */
    public function index()
    {
        $this->checkMenuPath(45, 46);
        $this->checkUserRule(47);
        $lists = $this->gamecoin_service->getPackageLists([]);
        return View::fetch('gamecoin/index', [
            'lists' => $lists,
        ]);
    }

    /**获取指定套餐信息
     * @return void
     */
    public function getGamecoinPackageInfo()
    {
        $pid = input('p_id', 0);
        if(!$pid){
            $this->error('参数不完整');
        }
        $info = $this->gamecoin_service->getPackageInfoById($pid);
        $this->success('success', '', $info);
    }

    /**保存套餐
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function saveGamecoinPackageInfo()
    {
        $this->gamecoin_service->saveGamecoinPackageInfo(input());
        $this->success('保存成功');
    }

    /**删除套餐
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deletePackage()
    {
        $pid = input('pid', 0);
        if(!$pid){
            $this->error('参数不完整');
        }
        $info = $this->gamecoin_service->getPackageInfoById($pid);
        $info->delete();
        $this->success('删除成功');
    }

    /**订单管理
     * @return string
     */
    public function orders()
    {
        $this->checkUserRule(47);
        $uniacid = $this->uniacid;
        $key = input('key');
        $start_get = input('start_get');
        $end_get = input('end_get');
        $order_id = input('order_id');
        $param = input();
        $pay_type = isset($param['pay_type']) ? $param['pay_type'] : -1;
        $suids = [];
        if($key){
            $suids = (new WdXcxUser())->searchUserByKey($key);
        }
        list($lists, $total_money, $total_get) = $this->gamecoin_service->getOrdersLists([
            'start_get' => $start_get,
            'end_get' => $end_get,
            'pay_type' => $pay_type,
            'order_id' => $order_id,
            'key' => $key,
            'suids' => $suids
        ]);
        return View::fetch('gamecoin/orders', [
            'lists' => $lists,
            'total_money' => $total_money,
            'total_get' => $total_get,
            'pay_type' => $pay_type,
            'se_key' => $key,
            'start_get' => $start_get,
            'end_get' => $end_get,
        ]);
    }
}