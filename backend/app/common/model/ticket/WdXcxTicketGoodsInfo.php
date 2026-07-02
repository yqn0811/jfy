<?php

namespace app\common\model\ticket;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxTicketGoodsInfo extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_ticket_goods_info';
    protected $autoWriteTimestamp = true;

    public function setTicketThumbAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 2) : '';
    }

    public function getTicketThumbAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 1) : '';
    }

    public function getTicketLableArrAttr($value)
    {
        return $this->ticket_lable ? explode(',', $this->ticket_lable) : '';
    }

    public function getInfoById($id)
    {
        return $this->where('id', $id)
            ->where('status', 1)
            ->find();
    }

    public function getInfoByGoodsId($goods_id, $create=false)
    {
        $info = $this->where('goods_id', $goods_id)->find();
        if(!$info){
            $this->create([
                'uniacid' => $this->uniacid,
                'goods_id' => $goods_id,
                'vip_discount' => 1,
                'status' => 1
            ]);
            $info = $this->where('goods_id', $goods_id)->find();
        }
        return $info;
    }

}