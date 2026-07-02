<?php

namespace app\common\model\catering;

use app\common\model\BaseModel;
use app\common\model\order\WdXcxUserCateringOrderDishes;
use think\model\concern\SoftDelete;

class WdXcxFood extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_food';
    protected $autoWriteTimestamp = true;

    public function specs()
    {
        return $this->hasMany(WdXcxFoodTypeValue::class, 'pid', 'id');
    }

    /**获取指定规格信息
     * @param $value_id
     * @return WdXcxFood|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSkuInfo($value_id)
    {
        $type = $this->where('id', $value_id)->find();
        if(!$type){
            throwError('当前规格不存在');
        }
        return $type;
    }


    public function getThumbAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 1) : '';
    }

    public function getFoodPicsAttr($value)
    {
        if($value){
            $value = json_decode($value, true);
            foreach ($value as &$item){
                $item = remote($this->uniacid, $item, 1);
            }
            return $value;
        }else{
            return [$this->thumb];
        }
    }

    /**指定菜品不存在
     * @param $id
     * @return WdXcxFood|array|mixed|\think\Model|null
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDishesById($id)
    {
        $info = $this->where('uniacid', $this->uniacid)->where('id', $id)->find();
        if(!$info){
            throwError('菜品不存在');
        }
        if($info->status != 1){
            throwError('当前菜品已下架');
        }
        return $info;
    }

    /**最低价
     * @return mixed
     */
    public function getShowPriceAttr()
    {
        return $this->specs()->min('price');
    }

    /**菜品月销量
     * @return int
     */
    public function getMonthSaleCountAttr()
    {
        $true_num = WdXcxUserCateringOrderDishes::where('dishes_id', $this->id)
            ->whereBetween('create_time', [strtotime('-30 day'), time()])
            ->sum('num');
        $vsalenum = $this->specs()->sum('vsalenum');
        return intval($true_num + $vsalenum);
    }

    public function getSpecsDataAttr()
    {
        $specs = $this->specs()->withoutField('salenum, vsalenum, create_time, update_time, delete_time')->select()->toArray();
        $comment = explode(',', $specs[0]['comment']);
        $result = [];
        foreach ($comment as $k => $v){
            $result[] = [
                'specs' => $v,
                'value' => array_values(array_unique(array_column($specs, 'type'.($k+1)))),
            ];
        }
        return $result;
    }
}