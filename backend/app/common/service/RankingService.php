<?php

namespace app\common\service;

use app\common\model\order\WdXcxUserRechargeOrderLists;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserBalanceRecord;
use app\common\model\user\WdXcxUserGiveBalanceRecord;
use app\common\model\user\WdXcxUserWxpayRecord;
use app\common\service\ylb\YlbApiService;
use think\cache\driver\Redis;

class RankingService
{

    /**获取用户消费金额统计排行数据
     * @param $flag
     * @param $time_data
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserConsumptionMoney($flag, $time_data)
    {
        $redis = new Redis(GetRedisConf());
        $result = $redis->get('ranking:user_consumption_money_' . $flag);
        if($result){
            return $result;
        }
        //获取用户余额消费金额统计
        $banlance_record = WdXcxUserBalanceRecord::where('change_type', 1)
            ->where('create_time', 'between', $time_data)
            ->group('user_id')
            ->field('user_id,sum(change_price) as total_money')
            ->select();
        //获取用户零钱消费金额统计
        $give_balance = WdXcxUserGiveBalanceRecord::where('change_type', 1)
            ->where('create_time', 'between', $time_data)
            ->group('user_id')
            ->field('user_id,sum(change_price) as total_money')
            ->select();
        //获取微信消费金额统计
        $wx_pay = WdXcxUserWxpayRecord::where('refund_order_id', '')
            ->where('create_time', 'between', $time_data)
            ->group('user_id')
            ->field('user_id,sum(pay_price) as total_money')
            ->select();
        //合并消费金额
        $total_data = [];
        foreach ($banlance_record as $item){
            $total_data[$item->user_id] = $item->total_money;
        }
        foreach ($give_balance as $give){
            if(isset($total_data[$give->user_id])){
                $total_data[$give->user_id] = bcadd($total_data[$give->user_id], $give->total_money, 2);
            }else{
                $total_data[$give->user_id] = $give->total_money;
            }
        }
        foreach ($wx_pay as $wx){
            if(isset($total_data[$wx->user_id])){
                $total_data[$wx->user_id] = bcadd($total_data[$wx->user_id], $wx->total_money, 2);
            }else{
                $total_data[$wx->user_id] = $wx->total_money;
            }
        }
        $result = [];
        foreach ($total_data as $k => $t_data){
            $result[] = [
                'user_id' => $k,
                'score_result_true' => $t_data
            ];
        }
        $total_money = array_column($result, 'score_result_true');
        array_multisort($total_money, SORT_DESC, $result);
        $sort_result = [];
        foreach ($result as $k => $item){
            if($k < 20){
                $item['user_info'] = $this->getUserNameAndAvatar($item['user_id']);
                if($item['user_info']){
                    $sort_result[] = $item;
                }
            }else{
                break;
            }
        }
        $redis->set('ranking:user_consumption_money_' . $flag, $sort_result, 2592000);
        return $sort_result;
    }

    /**获取用户积分数据排行
     * @param $flag
     * @return WdXcxUser[]|array|mixed|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserScoreSort($flag)
    {
        $redis = new Redis(GetRedisConf());
        $result = $redis->get('ranking:user_score_sort_' . $flag);
        if($result){
            return $result;
        }
        $user_score = WdXcxUser::where('mobile', '<>', '')
            ->order('integral desc, id desc')
            ->limit(20)
            ->field('id as user_id, integral')
            ->select()->toArray();
        $result = [];
        foreach ($user_score as &$item){
            $item['user_info'] = $this->getUserNameAndAvatar($item['user_id']);
            $item['score_result_true'] = $item['integral'];
            if($item['user_info']){
                $result[] = $item;
            }
        }
        $redis->set('ranking:user_score_sort_' . $flag, $result, 2592000);
        return $result;
    }

    /**用户充值金额排行
     * @param $flag
     * @param $time_data
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserRechargeMoney($flag, $time_data)
    {
        $redis = new Redis(GetRedisConf());
        $result = $redis->get('ranking:user_recharge_money_' . $flag);
        if($result){
            return $result;
        }
        $recharge_record = WdXcxUserRechargeOrderLists::where('status', 2)
            ->where('create_time', 'between', $time_data)
            ->group('user_id')
            ->field('user_id,sum(pay_price) as total_money')
            ->order('total_money desc, id desc')
            ->limit(20)
            ->select()->toArray();
        $result = [];
        foreach ($recharge_record as &$item){
            $item['user_info'] =  $this->getUserNameAndAvatar($item['user_id']);
            $item['score_result_true'] = $item['total_money'];
            if($item['user_info']){
                $result[] = $item;
            }
        }
        $redis->set('ranking:user_recharge_money_' . $flag, $result, 2592000);
        return $result;
    }

    /**获取用户游戏币排行
     * @param $flag
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserGameicon($flag)
    {
        $redis = new Redis(GetRedisConf());
        $result = $redis->get('ranking:user_gameicon_' . $flag);
        if($result){
            return $result;
        }
        $lists = (new YlbApiService())->getUserPropertyTopList(402);
        $lists = isset($lists['TopList']) ? $lists['TopList'] : [];
        $result = [];
        foreach ($lists as $item){
            $user_id =WdXcxUser::where('leaguer_id', $item['LeaguerID'])->value('id');
            if($user_id){
                $result[] = [
                    'user_id' => $user_id,
                    'user_info' => $this->getUserNameAndAvatar($user_id),
                    'score_result_true' => $item['Amount'],
                ];
            }
        }
        $redis->set('ranking:user_gameicon_' . $flag, $result, 2592000);
        return $result;
    }

    /**获取用户头像昵称
     * @param $user_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getUserNameAndAvatar($user_id, $leaguer_id=null)
    {
        if($user_id){
            $user = WdXcxUser::where('id', $user_id)->field('nickname, avatar')->find();
        }else{
            $user = WdXcxUser::where('leaguer_id', $leaguer_id)->field('nickname, avatar')->find();
        }
        if(!$user){
            return false;
        }
        if($user){
            $nickname = $user->nickname;
            if(strpos($user->getData('avatar'), 'users/user_default.png') !== false){
                $avatar = 'https://manage.4funinnovate.com/image/static/default-avatar.png';
            }else{
                $avatar = $user->avatar;
            }
        }else{
            $nickname = '超级玩家***';
            $avatar = 'https://manage.4funinnovate.com/image/static/default-avatar.png';
        }
        return compact('nickname', 'avatar');
    }
}