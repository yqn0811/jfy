<?php

namespace app\common\service\sign;

use app\common\model\integral_sign\WdXcxSignCon;
use app\common\model\integral_sign\WdXcxUserIntegralSignRecord;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserIntegralRecord;
use app\common\service\BaseService;
use app\common\service\ylb\YlbApiService;
use think\App;
use think\Exception;
use think\facade\Db;

class IntegralSignService extends BaseService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**获取用户签到的统计信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserSignInfo()
    {
        $total_count = 0;
        $user_score = 0;
        $max_continuity = 0;
        $user = $this->getUserInfo();
        if($user){
            $user_score = $user->integral;
            $total_count = WdXcxUserIntegralSignRecord::where('user_id', $user->id)->count();
            $max_continuity = WdXcxUserIntegralSignRecord::where('user_id', $user->id)
                ->order('create_time desc')
                ->limit(1)
                ->value('max_continuity');
        }
        return compact('total_count', 'user_score', 'max_continuity');
    }

    /**获取用户签到记录
     * @return \think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserSignRecordLists()
    {
        $user = $this->getUserInfo();
        $user_id = $user ? $user->id : -1;
        $lists = WdXcxUserIntegralSignRecord::where('user_id', $user_id)
            ->order('create_time desc')
            ->field('get_score, create_date, get_gamecoin')
            ->paginate(10)->each(function ($item){
                $item->create_date = str_replace('年', '.', $item->create_date);
                $item->create_date = str_replace('月', '.', $item->create_date);
                $item->create_date = str_replace('日', '', $item->create_date);
            });
        return $lists;
    }

    public function doUserSignExecute($user_id)
    {
        $user = (new WdXcxUser())->getUserById($user_id);
        $create_date = date('Y年n月j日');
        $record = WdXcxUserIntegralSignRecord::where([
            'user_id' => $user->id,
            'create_date' => $create_date
        ])->find();
        if($record){
            throwError('今天已经签到过了');
        }
        $record = new WdXcxUserIntegralSignRecord();
        $record->uniacid = $this->uniacid;
        $record->user_id = $user->id;
        $record->create_date = $create_date;
        $record->get_score = $this->getSignScore($user);
        $record->get_gamecoin = $this->getSignGamecoin($user);
        //查询总签到积分
        $last_day = date('Y年n月j日', strtotime('-1 day'));
        $last_record = WdXcxUserIntegralSignRecord::where([
            'user_id' => $user_id,
            'create_date' => $last_day
        ])->find();
        $record->max_continuity = $last_record ? $last_record->max_continuity + 1 : 1;
        Db::startTrans();
        try {
            $record->save();
            if($record->get_score){
                (new WdXcxUserIntegralRecord())->addRecord($user, [
                    'order_id' => '',
                    'change_integral' => $record->get_score,
                    'message' => '签到积分',
                    'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_SIGN
                ], WdXcxUserIntegralRecord::INTEGRAL_CHANGE_ADD);
            }
            if($record->get_gamecoin){
                //增加游戏币
                (new YlbApiService())->changeUserValues($user->leaguer_id, 402, $record->get_gamecoin, '签到获得游戏币');
            }
        }catch (Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();

    }

    /**获取本次签到可以获得的积分
     * @param $user
     * @return array|int|int[]
     */
    private function getSignScore($user)
    {
        $current_score = (new WdXcxSignCon())->getGradeScore($this->uniacid, $user->vip_grade, 2);
        $max_score = WdXcxSignCon::where('uniacid', $this->uniacid)->value('max_score');
        if($max_score){
            $total_score = WdXcxUserIntegralSignRecord::where('user_id', $user->id)->sum('get_score');
            $total_score = intval($total_score);
            if(($total_score + $current_score) > $max_score){
                if($total_score > $max_score){
                    $current_score = 0;
                }else{
                    $current_score = intval($max_score - $total_score);
                }
            }
        }
        return $current_score;
    }

    /**获取本次签到可以获得的游戏币
     * @param $user
     * @return array|int|int[]
     */
    private function getSignGamecoin($user)
    {
        $current_gamecoin = (new WdXcxSignCon())->getGradeGamecoin($this->uniacid, $user->vip_grade, 2);
        $max_gamecoin = WdXcxSignCon::where('uniacid', $this->uniacid)->value('max_gamecoin');
        if($max_gamecoin){
            $total_gamecoin = WdXcxUserIntegralSignRecord::where('user_id', $user->id)->sum('get_gamecoin');
            $total_gamecoin = intval($total_gamecoin);
            if(($total_gamecoin + $current_gamecoin) > $max_gamecoin){
                if($total_gamecoin > $max_gamecoin){
                    $current_gamecoin = 0;
                }else{
                    $current_gamecoin = intval($max_gamecoin - $total_gamecoin);
                }
            }
        }
        return $current_gamecoin;
    }

    /**获取签到首页日历数据
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSignIndexInfo($param)
    {
        $user = $this->getUserInfo();
        $current_month = $param['choose_month'] ? $param['choose_month'] :date('Y年n月');
        $calendar = $this->getCalendarData($current_month);
        $calendar_data = [];
        foreach ($calendar as $item){
            if($item){
                $temp['day'] = $item;
                if($user){
                    $has_record = WdXcxUserIntegralSignRecord::where([
                        'user_id' => $user->id,
                        'create_date' => $current_month.$item.'日',
                    ])->find();
                    $temp['is_sign'] = $has_record ? true : false;
                }else{
                    $temp['is_sign'] = false;
                }
                $temp['is_today'] = $current_month.$item == date('Y年n月j');
                $calendar_data[] = $temp;
            }else{
                $calendar_data[] = null;
            }

        }
        return compact('current_month', 'calendar_data');
    }

    /**组装日历数据
     * @param $current_month
     * @return array
     */
    private function getCalendarData($current_month)
    {
        $current_month = str_replace('年', '-', rtrim($current_month, '月'));
        $first_day = date('Y-m-01', strtotime($current_month));
        $week = getWeekInt(date('w', strtotime($first_day)));
        $calendar = [];
        for($i=1; $i<$week; $i++){
            $calendar[] = 0;
        }
        $days = date('t', strtotime($current_month));
        for ($i=0; $i<$days; $i++){
            $calendar[] = date('j', strtotime($first_day.'+'.$i.'days'));
        }
        return $calendar;
    }
}