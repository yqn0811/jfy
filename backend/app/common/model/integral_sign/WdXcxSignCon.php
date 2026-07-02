<?php

namespace app\common\model\integral_sign;

use think\Model;

class WdXcxSignCon extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_sign_con';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'score' => 'array',
        'gamecoin' => 'array',
    ];

    public function getBase($uniacid)
    {
        $set = $this->where('uniacid', $uniacid)->find();
        if(!$set){
            $set = new $this;
            $set->uniacid = $uniacid;
            $set->score = [];
            $set->max_score = 1000;
            $set->save();
        }
        return $set;
    }

    /**获取会员等级的随机积分
     * @param $uniacid
     * @param $grade
     * @param $type 1 获取积分区间  2 或者随机积分
     * @return array|int|int[]
     */
    public function getGradeScore($uniacid, $grade, $type=1)
    {
        $set = $this->getBase($uniacid);
        $score = $set->score;
        if(isset($score[$grade])){
            if($type == 1){
                return [$score[$grade]['min'], $score[$grade]['max']];
            }else{
                return rand($score[$grade]['min'], $score[$grade]['max']);
            }
        }else{
            if($type == 1){
                return [0, 0];
            }else{
                return 0;
            }
        }
    }

    /**获取会员等级的随机游戏币
     * @param $uniacid
     * @param $grade
     * @param $type
     * @return array|int|int[]
     */
    public function getGradeGamecoin($uniacid, $grade, $type=1)
    {
        $set = $this->getBase($uniacid);
        $gamecoin = $set->gamecoin;
        if(isset($gamecoin[$grade])){
            if($type == 1){
                return [$gamecoin[$grade]['min'], $gamecoin[$grade]['max']];
            }else{
                return rand($gamecoin[$grade]['min'], $gamecoin[$grade]['max']);
            }
        }else{
            if($type == 1){
                return [0, 0];
            }else{
                return 0;
            }
        }
    }


}