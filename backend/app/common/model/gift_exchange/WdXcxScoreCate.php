<?php

namespace app\common\model\gift_exchange;

use think\Model;

class WdXcxScoreCate extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_score_cate';
    protected $autoWriteTimestamp = true;

    public function getCates($uniacid){
        return $this->where('uniacid', $uniacid)
            ->order("num desc")
            ->select();
    }

    public function goods()
    {
        return $this->hasMany(WdXcxScoreShop::class, 'cid', 'id');
    }

}