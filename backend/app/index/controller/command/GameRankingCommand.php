<?php

namespace app\index\controller\command;

use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserBalanceRecord;
use app\common\model\user\WdXcxUserDiamondRecord;
use app\common\model\user\WdXcxUserGiveBalanceRecord;
use app\common\model\user\WdXcxUserPlayRecord;
use app\common\model\user\WdXcxUserPlayRewardRecord;
use app\common\model\user\WdXcxUserWxpayRecord;
use app\common\service\RankingService;
use app\index\model\WdXcxBase;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;
use think\facade\Config;
use think\facade\Db;

class GameRankingCommand extends Command
{
    protected function configure()
    {
        $this->setName('GameRanking')
            ->setDescription('游戏排行榜');

    }

    /**执行内容
     * @param Input $input
     * @param Output $output
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function execute(Input $input, Output $output)
    {
        //获取用户消费金额统计
        $start_time = strtotime('last monday', strtotime('-1 week'));
        $end_time = strtotime('next monday', $start_time) - 1;
        $flag = date('Y/m/d', $start_time).'-'.date('Y/m/d', $end_time);
//        $this->getUserConsumptionMoney($flag, [$start_time, $end_time]);
//        $this->getUserScoreSort($flag);
//        $this->getUserRechargeMoney($flag, [$start_time, $end_time]);
        $this->getUserGameicon($flag);

//        $ranking_type = Config::get('ylb.ranking_type');
//        if($ranking_type == 'week'){
//            $today = date('w');
//            $today = date('w', strtotime('2024-08-05'));
//            $flag_date = 1;
//        }else{
//            $today = date('Y-m-d');
//            $flag_date = date('Y-m-01');
//        }
//        if($today == $flag_date){
//            $output->writeln('***************** 排行结算奖励开始 ********************');
//            if($ranking_type == 'week'){
//                $this->gameRankingRewardWeek($output);
//            }else{
//                $this->gameRankingRewardMonth($output);
//            }
//            $output->writeln('***************** 排行结算奖励结束 ********************');
//        }else{
//            $output->writeln('非结算日期不结算');
//        }
    }

    /**获取用户游戏币排行
     * @param $flag
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getUserGameicon($flag)
    {
        (new RankingService())->getUserGameicon($flag);
    }

    /**用户充值排行
     * @param $flag
     * @param $time_data
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getUserRechargeMoney($flag, $time_data)
    {
        (new RankingService())->getUserRechargeMoney($flag, $time_data);
    }

    /**获取积分排行
     * @param $flag
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getUserScoreSort($flag)
    {
        (new RankingService())->getUserScoreSort($flag);
    }

    /**获取用户消费金额排行
     * @param $flag
     * @param $time_data
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getUserConsumptionMoney($flag, $time_data)
    {
        (new RankingService())->getUserConsumptionMoney($flag, $time_data);
    }

    /**结算奖励 数据操作 按周
     * @param $output
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function gameRankingRewardWeek($output)
    {
        $start_time = strtotime('last monday', strtotime('-1 week'));
        $end_time = strtotime('next monday', $start_time) - 1;
//        $start_time = strtotime(date('Y-m-01 00:00:00'));
//        $end_time = time();
        $reward_week = date('Y-m', $start_time) . '|' . date('W', $start_time);
        $has = WdXcxUserPlayRewardRecord::where('create_week', $reward_week)->find();
        if($has){
            $output->writeln($reward_week.'已结算');
            return;
        }
        $all_result = $this->getRewardSortLists($output, $start_time, $end_time);
        $lists = Config::get('ylb.terminal_ranking_name');
        $this->saveRewardSortData($output, $all_result['all_data'], date('Y-m', $start_time), $reward_week);
        $output->writeln('共计结算'.count($lists).'个项目， 共计'.$all_result['total_reward_count'].'条奖励数据');
    }


    /**结算奖励数据操作 按月
     * @param $output
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function gameRankingRewardMonth($output)
    {
        $start_time = strtotime(date('Y-m-01 00:00:00', strtotime('-1 month')));
        $end_time = strtotime(date('Y-m-01 00:00:00')) - 1;
//        $start_time = strtotime(date('Y-m-01 00:00:00'));
//        $end_time = time();
        $reward_month = date('Y-m', $start_time);
        $has = WdXcxUserPlayRewardRecord::where('create_month', $reward_month)
            ->where('create_week', '')
            ->find();
        if($has){
            $output->writeln($reward_month.'已结算');
            return;
        }
        $all_result = $this->getRewardSortLists($output, $start_time, $end_time);
        $lists = Config::get('ylb.terminal_ranking_name');
        $this->saveRewardSortData($output, $all_result['all_data'], $reward_month);
        $output->writeln('共计结算'.count($lists).'个项目， 共计'.$all_result['total_reward_count'].'条奖励数据');
    }

    /**保存排行奖励数据
     * @param $output
     * @param $all_data
     * @param $reward_month
     * @return void
     */
    private function saveRewardSortData($output, $all_data, $reward_month, $reward_week = '')
    {
        Db::startTrans();
        try {
            if(count($all_data) > 0){
                foreach ($all_data as $record_item){
                    if(count($record_item) > 0){
                        foreach ($record_item as $item_data){
                            $user = (new WdXcxUser())->getUserById($item_data['user_id']);
                            (new WdXcxUserDiamondRecord())->addRecord($user, [
                                'order_id' => '',
                                'change_diamond' => $item_data['change_diamond'],
                                'message' => $item_data['message'],
                                'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_REWARD,
                            ], WdXcxUserDiamondRecord::DIAMOND_CHANGE_ADD);
                        }
                    }
                }
            }
            $reward_record = new WdXcxUserPlayRewardRecord();
            $reward_record->record_info = $all_data;
            $reward_record->create_month = $reward_month;
            $reward_record->create_week = $reward_week;
            $reward_record->save();
        }catch (Exception $exception){
            Db::rollback();
            $output->writeln('结算失败:'.$exception->getMessage());
        }
        Db::commit();
    }

    /**获取排行数据
     * @param $output
     * @param $start_time
     * @param $end_time
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getRewardSortLists($output, $start_time, $end_time)
    {
        $lists = Config::get('ylb.terminal_ranking_name');
        $reward_set = WdXcxBase::where('uniacid', 1)->find()->about;
        $all_data = [];
        $total_reward_count = 0;
        foreach ($lists as $k => $item){
            $output->writeln('============ 【' . $item . '】结算开始 ==============');
            $result = (new WdXcxUserPlayRecord())->getUserScoreSort([
                'start_time' => $start_time,
                'end_time' => $end_time,
                'terminal_id' => $k
            ], 2);
            if($result->count() > 0){
                foreach ($result as $kk => $record){
                    $record->sort_num = $kk + 1;
                    $reward_diamond = $record->getRewardDiamond($k, $record->sort_num, $reward_set);
                    $all_data[$k][] = [
                        'user_id' => $record->user_id,
                        'score_result' => $record->score_result_true,
                        'change_diamond' => $reward_diamond,
                        'message' => '【' . $item . '】游戏周排行第'.$record->sort_num.'奖励',
                    ];
                    $total_reward_count += 1;
                }
            }
            $output->writeln('共计结算'.count($result).'条奖励数据');
            $output->writeln('============ 【' . $item . '】结算结束 ==============');
        }
        return compact('all_data', 'total_reward_count');
    }
}