<?php

namespace app\common\model\gift_exchange;

use think\Model;
use think\model\concern\SoftDelete;

class WdXcxExchangeInfo extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_exchange_info';
    protected $autoWriteTimestamp = true;

    public function getExchangeTypeStrAttr($value)
    {
        if($this->exchange_type == 1){
            return '兑换余额';
        }
        if($this->exchange_type == 2){
            return '钻石兑换积分';
        }
        if($this->exchange_type == 3){
            return '兑换实物';
        }
        if($this->exchange_type == 4){
            return '彩票兑换积分';
        }
    }

    public function getExchangeContentAttr($value)
    {
        if($this->exchange_type == 1){
            return $this->pay_diamond.'钻石兑换：'.$this->get_balance.'余额';
        }
        if($this->exchange_type == 2){
            return $this->pay_diamond.'钻石兑换：'.$this->get_score.'积分';
        }
        if($this->exchange_type == 3){
            return $this->pay_score .'积分兑换实物';
        }
        if($this->exchange_type == 4){
            return $this->pay_lottery.'彩票兑换：'.$this->get_lottery_score.'积分';
        }
    }

    public function setGiftThumbAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 2) : '';
    }

    public function getGiftThumbAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 1) : '';
    }

    public function setGiftPicsAttr($value)
    {
        if($value){
            foreach ($value as $k => $v){
                $value[$k] = remote($this->uniacid, $v, 2);
            }
            return json_encode($value);
        }
        return '';
    }

    public function getGiftPicsAttr($value)
    {
        if($value){
            $value = json_decode($value, true);
            foreach ($value as $k => $v){
                $value[$k] = remote($this->uniacid, $v, 1);
            }
            return $value;
        }
        return '';
    }

    /**获取列表
     * @param $uniacid
     * @param $type
     * @return WdXcxExchangeInfo[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGiftListsByType($uniacid)
    {
        $exchange_balance = $this->where('uniacid', $uniacid)
            ->where('exchange_type', 1)
            ->order('sort_num desc, id desc')
            ->field('id, uniacid, pay_diamond, get_balance')
            ->select();
        $diamond_exchange_score = $this->where('uniacid', $uniacid)
            ->where('exchange_type', 2)
            ->order('sort_num desc, id desc')
            ->field('id, uniacid, pay_diamond, get_score')
            ->select();
        $exchange_goods = $this->where('uniacid', $uniacid)
            ->where('exchange_type', 3)
            ->order('sort_num desc, id desc')
            ->field('id, uniacid, pay_score, gift_title, gift_thumb, gift_stock')
            ->select();
        $lottery_exchange_score = $this->where('uniacid', $uniacid)
            ->where('exchange_type', 4)
            ->order('sort_num desc, id desc')
            ->field('id, uniacid, pay_lottery, get_lottery_score')
            ->select();
        return compact('exchange_balance', 'diamond_exchange_score', 'exchange_goods', 'lottery_exchange_score');
    }

    /**获取兑换商品详情
     * @param $id
     * @return WdXcxExchangeInfo|array|mixed|Model|null
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGiftInfoById($uniacid, $id)
    {
        $info = $this->where('uniacid', $uniacid)
            ->where('id', $id)
            ->find();
        if(!$info){
            throwError('指定兑换物品不存在');
        }
        return $info;
    }

}