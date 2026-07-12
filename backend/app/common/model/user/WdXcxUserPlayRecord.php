<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\common\model\WdXcxBase;
use think\facade\Config;

class WdXcxUserPlayRecord extends BaseModel
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_play_record';
    protected $autoWriteTimestamp = true;

    public function voucher()
    {
        return $this->hasOne(WdXcxUserPlayVoucher::class, 'play_record', 'id');
    }

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    public function getCodeStrAttr()
    {
        $str = '';
        if($this->code == 1){
            $str = '余额消费';
        }
        if($this->code == 402){
            $str = '游戏币消费';
        }
        if($this->code == 499){
            $str = mb_strlen($this->summary) > 18 ? mb_substr($this->summary, 0, 16) . '...' : $this->summary;
        }
        return $str;
    }

    /**根据游戏设备ID获取结果类型
     * @param $value
     * @return int
     */
    public function setResultTypeAttr($value)
    {
        $terminal_result_time = Config::get('ylb.terminal_result_time');
        return in_array($this->terminal_id, $terminal_result_time) ? 2 : 1;
    }

    public function getScoreResultAttr($value)
    {
        if($this->result_type == 1){
            return $value;
        }else{
            if($value){
                $minutes = floor($value / (1000 * 60));
                $seconds = floor(($value - ($minutes * 1000 * 60)) / 1000);
                $milliseconds = $value % 1000;
                return $minutes."'".$seconds.'"'.$milliseconds;
            }else{
                return '';
            }
        }
    }

    /**游戏项目名称
     * @return mixed|string
     */
    public function getRecordTypeStrAttr()
    {
        if($this->record_type == 1){
            $terminal_name = Config::get('ylb.terminal_name');
            return empty($terminal_name[$this->terminal_id]) ? '游玩' : $terminal_name[$this->terminal_id];
        }else{
            return '闸机';
        }
    }





    /**获取游戏记录是否需要上传凭证
     * @return bool
     */
    public function getNeedUploadAttr()
    {
        $upload_terminal_id = Config::get('ylb.upload_terminal_id');
        $need_upload = false;
        if(in_array($this->terminal_id, $upload_terminal_id)){
            $need_upload = true;
        }
        return $need_upload;
    }

    /**获取排行数据
     * @param $param
     * @param $type 1 按分页查询所有数据 2 只查前50条数据
     * @return mixed
     */
    public function getUserScoreSort($param, $type=1)
    {
        $terminal_result_time = Config::get('ylb.terminal_result_time');
        $lists_sql = $this->whereBetween('create_time', [$param['start_time'], $param['end_time']])
            ->where('terminal_id', $param['terminal_id'])
            ->where('score_result', '>', 0)
            ->group('user_id');
        if(in_array($param['terminal_id'], $terminal_result_time)){
            $lists_sql = $lists_sql->field('id, user_id, score_result, min(score_result*1) as score_result_true, create_time, terminal_id, result_type')
                ->order('score_result_true asc, create_time asc, id asc');
        }else{
            $lists_sql = $lists_sql->field('id, user_id, score_result, max(score_result*1) as score_result_true, create_time, terminal_id, result_type')
                ->order('score_result_true desc, create_time asc, id asc');
        }
        if($type == 1){
            $lists = $lists_sql->paginate([
                'list_rows' => 10,
                'query' => input(),
            ]);
            $curr_page = $lists->currentPage();
            $lists->each(function ($item, $k)use($curr_page){
                $true_result = WdXcxUserPlayRecord::where([
                    'user_id' => $item->user_id,
                    'terminal_id' => $item->terminal_id,
                    'score_result' => $item->score_result_true,
                ])->value('create_time');
                if($true_result){
                    $item->create_time = $true_result;
                }
                $item->sort_num = $k + 1 + ($curr_page - 1) * 10;
                $item->reward_diamond = $item->getUserRewardDiamond($item->terminal_id, $item->sort_num, $item->user_id, $item->create_time);
                $item->score_result = $item->score_result_true;
            });
        }else{
            $lists = $lists_sql->limit(50)->select();
        }
        return $lists;
    }

    /**获取用户指定时间段内指定项目的最大分数
     * @param $param
     * @return mixed
     */
    public function getUserMaxScoreRecord($param)
    {
        $terminal_result_time = Config::get('ylb.terminal_result_time');
        $lists_sql = $this->whereBetween('create_time', [$param['start_time'], $param['end_time']])
            ->where('terminal_id', $param['terminal_id'])
            ->where('score_result', '>', 0)
            ->where('user_id', $param['user_id']);
        if(in_array($param['terminal_id'], $terminal_result_time)){
            $result = $lists_sql->field('id, user_id, score_result, create_time, terminal_id, result_type')
                ->order('score_result asc')
                ->find();
        }else{
            $result = $lists_sql->field('id, user_id, score_result, create_time, terminal_id, result_type')
                ->order('score_result desc')
                ->find();
        }
        return $result;
    }

    /**显示用户获取的钻石数量
     * @param $terminal_id
     * @param $sort_num
     * @param $user_id
     * @param $create_time
     * @param $flag 1 带文字  2 不带文字
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserRewardDiamond($terminal_id, $sort_num, $user_id, $create_time, $flag=1)
    {
        $ranking_type = Config::get('ylb.ranking_type');
        $create_time = strtotime($create_time);
        if($ranking_type == 'week'){
            $date_flag = strtotime('this week Monday');
        }else{
            $date_flag = strtotime(date('Y-m-01 00:00:00'));
        }
        $reward_set = WdXcxBase::where('uniacid', 1)->find()->about;
        $diamond = $this->getRewardDiamond($terminal_id, $sort_num, $reward_set);
        $result = $flag == 1 ? '预计获得：'.$diamond : $diamond;
        if($create_time < $date_flag){ //当月数据预估
            $diamond = 0;
            if($ranking_type == 'week'){
                $create_week = date('Y-m', $create_time) . '|' . date('W', $create_time);
                $reward_record = WdXcxUserPlayRewardRecord::where('create_week', $create_week)->value('record_info');
            }else{
                $create_month = date('Y-m', $create_time);
                $reward_record = WdXcxUserPlayRewardRecord::where('create_month', $create_month)
                    ->where('create_week', '')
                    ->value('record_info');
            }
            if($reward_record){
                $reward_record = json_decode($reward_record, true);
                foreach ($reward_record as $k => $item){
                    if($k == $terminal_id){
                        if(count($item) > 0){
                            foreach ($item as $it_value){
                                if($it_value['user_id'] == $user_id){
                                    $diamond = $it_value['change_diamond'];
                                }
                            }
                        }
                    }
                }
            }
            return $flag == 1 ? '获得：'.$diamond : $diamond;
        }
        return $result;
    }

    /**获取钻石奖励数据
     * @param $terminal_id
     * @param $sort_num
     * @param $reward_set
     * @return int
     */
    public function getRewardDiamond($terminal_id, $sort_num, $reward_set)
    {
        $reward_diamond = 0;
        if(isset($reward_set[$terminal_id])){
            $set = $reward_set[$terminal_id];
            if($sort_num == 1){
                $reward_diamond = $set['reward_1'];
            }elseif ($sort_num == 2){
                $reward_diamond = $set['reward_2'];
            }elseif ($sort_num == 3){
                $reward_diamond = $set['reward_3'];
            }elseif ($sort_num > 3 && $sort_num < 11){
                $reward_diamond = $set['reward_410'];
            }elseif ($sort_num > 10 && $sort_num < 51){
                $reward_diamond = $set['reward_1150'];
            }
        }
        return $reward_diamond;
    }

}