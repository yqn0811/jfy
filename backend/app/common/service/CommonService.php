<?php

namespace app\common\service;

use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserCollectPics;
use app\common\model\user\WdXcxUserPlayRecord;
use app\common\service\album\AiResourceBridgeService;
use app\common\model\WdXcxPic;
use app\common\model\WdXcxBase;
use app\common\service\upload\UploadService;
use think\App;
use think\facade\Config;

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
    public function uploadImage($param, $uid=0, $requestParam=[])
    {
        $new_param = $requestParam ?: $this->request->post();
        $fileType = isset($new_param['file_type']) ? (int)$new_param['file_type'] : 1;
        if(!in_array($fileType, [1, 2])){
            $fileType = 1;
        }
        try{
            $data = (new UploadService($this->uniacid))->uploadImages([
                'files' => $param,
                'flag' => 1,
                'gid' => 0,
                'uid' => $uid,
                'file_type' => $fileType,
                'original_names' => $this->getUploadOriginalNames($new_param),
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
                        $bridge->safeSyncPicture($uid, $picture, [
                            'role' => 'upload',
                            'file_hash' => strtolower(trim((string)($new_param['file_hash'] ?? ''))),
                            'content_hash' => strtolower(trim((string)($new_param['content_hash'] ?? ($new_param['file_hash'] ?? '')))),
                        ]);
                    }
                }
            }
        }catch (\Exception $exception){
            throwError($exception->getMessage());
        }
        return $data[0];
    }

    private function getUploadOriginalNames($param)
    {
        $names = [];
        foreach (['original_name', 'filename', 'file_name', 'name'] as $field) {
            if (!isset($param[$field]) || $param[$field] === '') {
                continue;
            }
            $value = $param[$field];
            if (is_array($value)) {
                foreach ($value as $index => $name) {
                    if ($name !== '') {
                        $names[$index] = (string)$name;
                    }
                }
            } elseif (empty($names)) {
                $names['default'] = (string)$value;
            }
        }
        return $names;
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

    public function memberUpgradeConfig()
    {
        return [
            "show_upgrade" => 1,
            "upgrade_url" => "https://pic.jfyuntu.com/assets/page/product-list.html",
        ];
    }

    public function getWorkbenchMenu()
    {
        $set = (new \app\common\model\system_set\WdXcxWorkbenchMenuSet())->getBase($this->uniacid);
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
