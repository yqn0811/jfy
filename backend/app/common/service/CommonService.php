<?php

namespace app\common\service;

use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserCollectPics;
use app\common\model\user\WdXcxUserPlayRecord;
use app\common\service\album\AiResourceBridgeService;
use app\index\model\WdXcxPic;
use app\index\model\WdXcxBase;
use app\index\service\upload\UploadService;
use think\App;
use think\facade\Config;
use think\facade\Db;

class CommonService extends BaseService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**上传图片
     * @param $param
     * @return mixed
     * @throws \cores\exception\BaseException
     */
    public function uploadImage($param, $uid=0)
    {
        $new_param = $this->request->post();
        try{
            $data = (new UploadService($this->uniacid))->uploadImages([
                'files' => $param,
                'flag' => 1,
                'gid' => 0,
                'uid' => $uid,
                'file_type' => 1,
            ]);
            if(!empty($new_param['collect_flag'])){
                foreach ($data as $pic){
                    WdXcxUserCollectPics::create([
                        'pic_id' => $pic['pid'],
                        'uid' => request()->userID(),
                        'collect_date' => date('Y-m-d')
                    ]);
                }
            }
            if ((int)$uid > 0 && !empty($data)) {
                $bridge = new AiResourceBridgeService($this->app);
                foreach ($data as $pic) {
                    if (empty($pic['pid'])) {
                        continue;
                    }
                    $picture = WdXcxPic::where('id', $pic['pid'])->find();
                    if ($picture) {
                        $bridge->safeSyncPicture($uid, $picture, ['role' => 'upload']);
                    }
                }
            }
        }catch (\Exception $exception){
            throwError($exception->getMessage());
        }
        return $data[0];
    }

    /**获取首页基础信息
     * @return array
     */
    public function indexBaseInfo()
    {
        $base = WdXcxBase::where('uniacid', $this->uniacid)->find();
        $result = [
            'index_banner' => $base->index_banner,
            'base_color_t' => $base->base_color_t,
            'video' => $base->video,
            'index_about_title' => $base->index_about_title,
        ];
        $tabbar = $base->tabbar;
        $active_info = [];
        if($tabbar!=null && count($tabbar) > 0){
            foreach ($tabbar as $item){
                if(strtotime($item['active_start']) < time() && strtotime($item['active_end']) > time()){
                    $active_info[] = $item;
                }
            }
        }
        $result['active_info'] = $active_info;
        return $result;
    }

    /**获取公共基础信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function commonBaseInfo()
    {
        $base = WdXcxBase::where('uniacid', $this->uniacid)->field('uniacid, about, news_link, kf_link, kf_ewm, folder_count, share_count, down_count')->find();
        $result = [
            'about' => $base->about,
            'news_link' => $base->news_link,
            'kf_link' => $base->kf_link,
            'kf_ewm' => $base->kf_ewm,
            'share_thumb' => $base->share_thumb,
            'folder_count' => $base->folder_count,
            'share_count' => $base->share_count,
            'down_count' => $base->down_count,
        ];
        return $result;
    }

    public function getWorkbenchMenu()
    {
        $set = (new \app\common\model\system_set\WdXcxWorkbenchMenuSet())->getBase($this->uniacid);
        if (!$this->isMemberUpgradeVisible()) {
            $set = $this->hideMemberUpgradeMenu($set);
        }
        return $set;
    }

    public function getMemberUpgradeConfig()
    {
        return [
            'show_upgrade' => $this->isMemberUpgradeVisible() ? 1 : 0,
            'mini_program_review_mode' => $this->isMemberUpgradeVisible() ? 0 : 1,
            'upgrade_url' => 'https://pic.jfyuntu.com/assets/page/product-list.html',
        ];
    }

    private function isMemberUpgradeVisible()
    {
        $config = $this->getMemberUpgradeSwitch();
        return (int)($config['show_upgrade'] ?? 0) === 1;
    }

    private function getMemberUpgradeSwitch()
    {
        $this->ensureFeatureSwitchTable();
        $row = Db::name('wd_xcx_feature_switch')
            ->where('uniacid', $this->uniacid)
            ->where('switch_key', 'member_upgrade')
            ->find();
        if (!$row) {
            $now = time();
            Db::name('wd_xcx_feature_switch')->insert([
                'uniacid' => $this->uniacid,
                'switch_key' => 'member_upgrade',
                'switch_name' => '会员升级入口',
                'is_enabled' => 0,
                'config_json' => json_encode(['review_mode' => 1], JSON_UNESCAPED_UNICODE),
                'create_time' => $now,
                'update_time' => $now,
            ]);
            $row = [
                'is_enabled' => 0,
                'config_json' => json_encode(['review_mode' => 1], JSON_UNESCAPED_UNICODE),
            ];
        }
        $config = json_decode((string)($row['config_json'] ?? ''), true);
        if (!is_array($config)) {
            $config = [];
        }
        $config['show_upgrade'] = (int)($row['is_enabled'] ?? 0);
        return $config;
    }

    private function ensureFeatureSwitchTable()
    {
        $tables = Db::query("SHOW TABLES LIKE 'wd_xcx_feature_switch'");
        if ($tables) {
            return;
        }
        Db::execute("
            CREATE TABLE IF NOT EXISTS `wd_xcx_feature_switch` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `uniacid` int(11) NOT NULL DEFAULT 1,
                `switch_key` varchar(64) NOT NULL,
                `switch_name` varchar(100) NOT NULL DEFAULT '',
                `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
                `config_json` text DEFAULT NULL,
                `create_time` int(11) DEFAULT NULL,
                `update_time` int(11) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_uniacid_switch_key` (`uniacid`,`switch_key`),
                KEY `idx_switch_key` (`switch_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='前端功能展示开关';
        ");
    }

    private function hideMemberUpgradeMenu($set)
    {
        foreach ($set as &$group) {
            foreach ($group as $key => $items) {
                if (!is_array($items)) {
                    continue;
                }
                $filtered = [];
                foreach ($items as $item) {
                    $icon = isset($item['icon']) ? (string)$item['icon'] : '';
                    $title = isset($item['title']) ? (string)$item['title'] : '';
                    if ($icon === 'vip' || $title === '升级会员') {
                        continue;
                    }
                    $filtered[] = $item;
                }
                $group[$key] = $filtered;
            }
        }
        unset($group);
        return $set;
    }

    /**获取游戏排行榜
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserPlayRankingListsNew($param)
    {
//        ['卡路里', '游戏币', '积分', '充值'];
        $date_index = isset($param['date_index']) ? $param['date_index'] : 2; // 0 上上周  1 上周 2 本周
        $game_key = isset($param['game_key']) ? $param['game_key'] : 0;
        $date_result = $this->getRankingWeekTime();
        $start_time = $date_result[$date_index]['start_time'];
        $today = date('w');
        $end_time = $date_index == 2 ? strtotime(date('Y-m-d 00:00:00')) - 1 : $date_result[$date_index]['end_time'];
        $ranking_service = new RankingService();
        $flag = date('Y/m/d', $start_time).'-'.date('Y/m/d', $end_time);
        if($today == 1){
            $end_time = strtotime(date('Y-m-d 23:59:59'));
            $flag = date('Y/m/d', $start_time).'-'.date('Y/m/d');
        }
        if($game_key == 0){
            $result = $ranking_service->getUserConsumptionMoney($flag, [$start_time, $end_time]);
        }else if($game_key == 1){
            $result = $ranking_service->getUserGameicon($flag);
        }else if($game_key == 2){
            $result = $ranking_service->getUserScoreSort($flag);
        }else{
            $result = $ranking_service->getUserRechargeMoney($flag, [$start_time, $end_time]);
        }
        $current_user = null;
        $user_info = $this->getUserInfo();
        foreach ($result as $k => &$item){
            $item['sort_num'] = $k + 1;
            if($user_info && $item['user_id'] == $user_info->id){
                $current_user = $item;
            }
        }
        if(!$current_user && $user_info){
            $current_user = [
                'user_id' => $user_info->id,
                'user_info' => [
                    'avatar' => $user_info->avatar,
                    'nickname' => $user_info->nickname,
                ],
                'score_result_true' => 0,
                'sort_num' => ''
            ];
        }
        return compact('current_user', 'result');
    }

    /**获取奖励排行榜
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserPlayRankingLists($param)
    {
//        $lists = Config::get('ylb.terminal_name');
        $lists = Config::get('ylb.terminal_ranking_name');
        $terminal_ids = array_keys($lists);
        $terminal_id = isset($terminal_ids[$param['game_key']]) ? $terminal_ids[$param['game_key']] : $terminal_ids[0];
        $date_index = isset($param['date_index']) ? $param['date_index'] : 2;
        $date_result = $this->getRecordData();
        $start_time = $date_result[$date_index]['start_time'];
        $end_time = $date_result[$date_index]['end_time'];
        $result = (new WdXcxUserPlayRecord())->getUserScoreSort([
            'start_time' => $start_time,
            'end_time' => $end_time,
            'terminal_id' => $terminal_id
        ], 2);
        $current_user = null;
        $user_info = $this->getUserInfo();
        foreach ($result as $k => &$item){
            $item->user_info = $item->UserInfo;
            $item->sort_num = $k + 1;
//            $item->reward_diamond = $item->getRewardDiamond($terminal_id, $item->sort_num, $reward_set);
            $item->reward_diamond = $item->getUserRewardDiamond($item->terminal_id, $item->sort_num, $item->user_id, $item->create_time, 2);
            if($user_info && $item->user_id == $user_info->id){
                $current_user = $item;
            }
            $item->score_result = $item->score_result_true;
            $item->score_result_true = $item->score_result;
        }
        if(!$current_user && $user_info){
            $use_record = (new WdXcxUserPlayRecord())->getUserMaxScoreRecord([
                'start_time' => $start_time,
                'end_time' => $end_time,
                'terminal_id' => $terminal_id,
                'user_id' => $user_info->id
            ]);
            $current_user = [
                'user_info' => $user_info,
                'sort_num' => '',
                'score_result_true' => 0,
                'reward_diamond' => 0,
            ];
            if($use_record){
                $current_user['score_result_true'] = $use_record->score_result;
            }
        }
        return compact('current_user', 'result');
    }

    /**获取排行榜时间显示值
     * @return array
     */
    public function getRecordData()
    {
        $ranking_type = Config::get('ylb.ranking_type');
        if($ranking_type == 'week'){
            $result = $this->getRankingWeekTime();
        }else{
            $result = $this->getRankingMonthTime();
        }
        return $result;
    }

    private function getRankingWeekTime($count = 3)
    {
        $result = [];
        for ($i = 0; $i < $count; $i++){
            if($i == 0){
                $start_time = strtotime('this week Monday');
                $end_time = strtotime('this week Sunday 23:59:59');
                $date_tips = '奖励将在'.date('n月j日', $end_time+2).'自动结算,届时自动发放至个人账户';
            }else{
                $start_time = strtotime('last monday', strtotime("-$i week"));
                $end_time = strtotime('next monday', $start_time) - 1;
                $date_tips = '奖励已在'.date('n月j日', $end_time+2).'自动结算,并自动发放至个人账户';
            }
            $date_str = date('n.j', $start_time).'~'.date('n.j', $end_time);

            $result[] = compact('start_time', 'end_time', 'date_str', 'date_tips');
        }
        return array_reverse($result);
    }

    private function getRankingMonthTime($count = 3)
    {
        $result = [];
        for ($i = 0; $i < $count; $i++){
            if($i == 0){
                $start_time = strtotime(date('Y-m-01'));
                $end_time = strtotime(date('Y-m-t 23:59:59'));
                $date_tips = '奖励将在'.date('n月j日', $end_time+2).'自动结算,届时自动发放至个人账户';
            }else{
                $start_time = strtotime(date('Y-m-01', strtotime("-$i month", strtotime(date('Y-m-15')))));
                $end_time = strtotime(date('Y-m-t 23:59:59', strtotime("-$i month", strtotime(date('Y-m-15')))));
                $date_tips = '奖励已在'.date('n月j日', $end_time+2).'自动结算,并自动发放至个人账户';
            }
            $date_str = date('n.j', $start_time).'~'.date('n.j', $end_time);
            $result[] = compact('start_time', 'end_time', 'date_str', 'date_tips');
        }
        return array_reverse($result);
    }

    /**获取新人有礼数据
     * @param $param
     * @return \app\common\model\system_set\WdXcxNewuserGiftRecord|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserNewGiftLists($param)
    {
        if(empty($param['openid'])){
            return null;
        }
        $user_info = (new WdXcxUser())->getByOpenid($param['openid']);
        if(!$user_info){
            return null;
        }
        if($user_info->getData('join_time') > 0 && $user_info->getData('join_time') < Config::get('comm_config.new_user')){
            return null;
        }
        return null;
    }

    /**新人领取礼包
     * @param $user_opeind
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function receiveUserNewGiftLists($user_opeind)
    {
        $user_info = (new WdXcxUser())->getByOpenid($user_opeind);
        if(!$user_info){
            throwError('用户不存在');
        }
        if($user_info->getData('join_time') > 0 && $user_info->getData('join_time') < Config::get('comm_config.new_user')){
            throwError('非有效期期内新用户');
        }
        throwError('活动已结束');
    }

}
