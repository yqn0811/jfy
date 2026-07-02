<?php

namespace app\index\controller;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\coupon\WdXcxUserCoupon;
use app\common\model\order\WdXcxUserOrderLists;
use app\common\model\order\WdXcxUserRechargeOrderLists;
use app\common\model\order\WdXcxUserTicketOrderLists;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserBalanceRecord;
use app\common\model\user\WdXcxUserGiveBalanceRecord;
use app\common\service\ylb\YlbApiService;
use app\index\model\WdXcxPic;
use think\cache\driver\Redis;
use think\facade\Config;
use think\facade\View;

class Datashow  extends IndexBaseController
{
    public function index()
    {
        $this->checkMenuPath(1, 2);
        $this->checkUserRule(2);
        $top_data = $this->getTopData();
        $chart_data = $this->getRechargeChartData();
        $thirty_data = $this->getThirtyData();
        $recharge_sort = $this->getUserRechargeSort();
        $tickets_data = $this->getUserTicketSort();
//        dd($tickets_data);
        return View::fetch('datashow/index', [
            'node_id' => [],
            'top_data' => $top_data,
            'chart_data' => json_encode($chart_data),
            'thirty_data' => $thirty_data,
            'recharge_sort' => $recharge_sort,
            'tickets_data' => $tickets_data
        ]);
    }

    public function scan()
    {
        $this->checkUserRule(3);
        return View::fetch('datashow/scan', []);
    }

    /**扫码核销
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkScanInfo()
    {
        $this->checkUserRule(3);
        $uniacid = $this->uniacid;
        $search_key = $this->request->param('search_key');
        $order = WdXcxUserOrderLists::where('uniacid', $uniacid)
            ->whereLike('order_id', '%'.$search_key.'%')
            ->find();
        if(!$order){
            //卡券
            $user_coupon = WdXcxUserCoupon::whereLike('qrcode_id', '%'.$search_key.'%')->find();
            if(!$user_coupon){
                $this->error('未查询到信息');
            }
            $order_id = $user_coupon->qrcode_id;
        }else{
            $order_id = $order->order_id;
        }
        $url = $this->getOrderUrl($order_id);
        $this->success('success', '', $url);
    }

    /**检索用户
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkScanUser()
    {
        $this->checkUserRule(3);
        $uniacid = $this->uniacid;
        $search_key = $this->request->param('search_key');
        if(strlen($search_key) > 11){
            if(strpos($search_key, '+GIVEBALANCE') !== false){
                $search_key = str_replace('+GIVEBALANCE', '', $search_key);
            }
            $leaguer_id = (new YlbApiService())->decodeUserQrcode($search_key);
            if(!$leaguer_id){
                $this->error('二维码信息异常');
            }
            $user = WdXcxUser::where('uniacid', $uniacid)
                ->where('leaguer_id', $leaguer_id)
                ->find();
        }else{
            $user = WdXcxUser::where('uniacid', $uniacid)
                ->where('mobile', $search_key)
                ->find();
        }

        if(!$user){
            $this->error('未查询到信息');
        }
        $this->success('success', '', [
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'realname' => $user->realname,
            'balance' => str_replace(',', '', $user->UserBalance),
            'mobile' => $user->mobile,
            'give_balance' => $user->give_balance,
            'id' => $user->id
        ]);
    }

    /**后台扫码扣除用户零钱
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeUserBalance()
    {
        $user_id = input('user_id');
        $pay_price = input('pay_price');
        $message = input('message');
        $search_key = input('search_key');
        if(!$user_id || !$pay_price || !$search_key){
            $this->error('操作参数不完整');
        }
        if($pay_price < 0){
            $this->error('金额不能小于0');
        }
        $user = WdXcxUser::find($user_id);
        if(!$user){
            $this->error('指定会员不存在');
        }
        $use_blance = '';
        if(strlen($search_key) > 11){
            if(strpos($search_key, '+GIVEBALANCE') !== false){
                $search_key = str_replace('+GIVEBALANCE', '', $search_key);
                $use_blance = 'give_balance';
            }
            $leaguer_id = (new YlbApiService())->decodeUserQrcode($search_key);
            if(!$leaguer_id){
                $this->error('二维码信息异常');
            }
            if($user->leaguer_id != $leaguer_id){
                $this->error('用户信息不正确');
            }
        }else{
            if($user->mobile != $search_key){
                $this->error('手机号码不正确');
            }
        }
        if($use_blance == 'give_balance'){
            if(bccomp($user->give_balance, $pay_price, 2) == -1){
                $this->error('用户零钱不足');
            }
            (new WdXcxUserGiveBalanceRecord())->addRecord($user, [
                'order_id' => generateOrderId('S'),
                'change_price' => $pay_price,
                'message' => $message ? $message : '后台操作',
                'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_BACKEND,
            ]);
        }else{
            $user_balance = str_replace(',', '', $user->UserBalance);
            if(bccomp($user_balance, $pay_price, 2) == -1){
                $this->error('用户余额不足');
            }
            (new WdXcxUserBalanceRecord())->addRecord($user, [
                'change_price' => $pay_price,
                'order_id' => generateOrderId('S'),
                'message' => $message ? $message : '后台操作',
                'user_id' => $user->id,
                'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_BACKEND_SCAN
            ]);
        }

        if(strlen($search_key) > 11){
            $result = [
                'type' => 100,
                'msg' => '消费成功',
                'money' => $pay_price,
                'use_info' => $message ? $message : '后台操作',
            ];
            $redis = new Redis(GetRedisConf());
            $redis->set($user->leaguer_id.'_scan_show', json_encode($result, JSON_UNESCAPED_UNICODE), Config::get('ylb.push_tips_times'));
        }
        $this->success('操作成功');

    }

    /**查询结果跳转地址
     * @param $order_id
     * @return string
     */
    private function getOrderUrl($order_id)
    {
        $order_id_type = substr($order_id, 0, 1);
        switch ($order_id_type){
            case 'R': //预约
                $url = Url('AppointmentController/orders').'?order_id='.$order_id.'&op=4';
                break;
            case 'F': //点餐
                $url = Url('RestaurantController/index').'?order_id='.$order_id;
                break;
            case 'Q': //卡券
                $url = Url('CouponsController/records').'?qrcode_id='.$order_id;
                break;
            case 'E': //兑换
                $url = Url('GiftExchangeController/orders').'?order_id='.$order_id.'&op=3';
                break;
            case 'C': //充值
                $url = Url('WxuserController/recharge').'?order_id='.$order_id;
                break;
            case 'T': //通票
                $url = Url('TicketingController/orders').'?order_id='.$order_id;
                break;
            case 'V': //消费
                $url = Url('WxuserController/consumption').'?order_id='.$order_id.'&op=xf';
                break;
            case 'G': //游戏币
                $url = Url('GamecoinController/orders').'?order_id='.$order_id;
                break;
        }
        return $url;
    }

    /**顶部数据
     * @return array
     * @throws \think\db\exception\DbException
     */
    private function getTopData()
    {
        $total_visit = 1252;
        $total_user_count = WdXcxUser::where('uniacid', $this->uniacid)->count();
        $total_vip_count = WdXcxUser::where('uniacid', $this->uniacid)
            ->where('vip_grade', '>', 0)
            ->count();
        $total_current_money = WdXcxAlbumFolder::where('id', '>', 0)->count();
        return compact('total_visit', 'total_user_count', 'total_vip_count', 'total_current_money');
    }

    /**图表数据
     * @return array
     * @throws \think\db\exception\DbException
     */
    private function getRechargeChartData()
    {
        $chart_count = [];
        $chart_money = [];
        $chart_date = [];
        $tod = date("Y-m-d",time());
        for($i = 6; $i >= 0; $i--){
            $stt = "$tod -".$i." days";
            $btime = strtotime(date("Y-m-d 00:00:00", strtotime($stt)));
            $etime = strtotime(date("Y-m-d 23:59:59", strtotime($stt)));
            $recharge_money = WdXcxPic::whereBetween('create_time', [$btime, $etime])
                ->count();
            $recharge_count = WdXcxAlbumFolder::whereBetween('create_time', [$btime, $etime])
                ->count();
            $chart_date[] = date('m-d', $btime);
            $chart_count[] = $recharge_count;
            $chart_money[] = $recharge_money;
        }
        return compact('chart_date', 'chart_count', 'chart_money');
    }

    /**获取30天数据
     * @return array
     * @throws \think\db\exception\DbException
     */
    private function getThirtyData()
    {
        $btime = strtotime(date('Y-m-d 00:00:00', strtotime('-29 days')));
        $etime = time();
        $total_count = WdXcxAlbumFolder::whereBetween('create_time', [$btime, $etime])
            ->count();
        $total_money = WdXcxPic::whereBetween('create_time', [$btime, $etime])
            ->count();
        return compact('total_count', 'total_money');
    }

    /**用户充值排行
     * @return mixed
     */
    public function getUserRechargeSort()
    {
        $data = WdXcxAlbumFolder::group('uid')
            ->field('uid, count(*) as total_count')
            ->order('total_count desc')
            ->limit(5)
            ->select();
        return $data;
    }

    public function getUserTicketSort()
    {
        $data = WdXcxPic::where('uid', '>', 0)
            ->group('uid')
            ->field('uid, sum(size) as total_size')
            ->order('total_size desc')
            ->limit(5)
            ->select();
        foreach ($data as &$item){
            $item->total_count = WdXcxPic::where('uid', $item->uid)->count();
            $item->total_size = bcdiv($item->total_size, 1024*1024*1024, 4) . 'G';
        }
        return $data;
    }

    public function welcomeIndex()
    {
        return View::fetch('datashow/welcome_index');
    }


}