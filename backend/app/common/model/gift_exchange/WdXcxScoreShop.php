<?php

namespace app\common\model\gift_exchange;

use think\Model;
use think\model\concern\SoftDelete;

class WdXcxScoreShop extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_score_shop';
    protected $autoWriteTimestamp = true;


    public function getPros($uniacid, $where){
        return $this->where($where)
            ->where('uniacid',$uniacid)
            ->order('num desc, id desc')
            ->paginate(10);
    }

    public function setTextAttr($value)
    {
        if($value){
            foreach ($value as $k => $v){
                $value[$k] = remote($this->uniacid, $v, 2);
            }
            return implode(',', $value);
        }
        return '';
    }

    public function getTextAttr($value)
    {
        if($value){
            $value = explode(',', $value);
            foreach ($value as $k => $v){
                $value[$k] = remote($this->uniacid, $v, 1);
            }
            return $value;
        }
        return '';
    }

}