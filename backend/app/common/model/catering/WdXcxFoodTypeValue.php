<?php

namespace app\common\model\catering;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxFoodTypeValue extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_food_type_value';
    protected $autoWriteTimestamp = true;


    public function getSpecsValueAttr()
    {
        $str_arry = [$this->type1];
        if($this->type2){
            $str_arry[] = $this->type2;
        }
        if($this->type3){
            $str_arry[] = $this->type3;
        }
        return implode(',', $str_arry);
    }
}