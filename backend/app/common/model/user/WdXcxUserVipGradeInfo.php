<?php

namespace app\common\model\user;

use think\Model;
use think\model\concern\SoftDelete;

class WdXcxUserVipGradeInfo extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_vip_grade_info';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'change_log' => 'array',
    ];

    public function getEndTimeStrAttr()
    {
        return $this->end_time == 0 ? '永久有效' : ($this-> grade_level == 0 ? '永久有效' : date('Y-m-d', $this->end_time));
    }

    public function getGradeNameAttr()
    {
        if($this->grade_level){
            return WdXcxVipgrade::where('grade_level', $this->grade_level)->where('uniacid', $this->uniacid)->value('grade_name');
        }else{
            return '普通用户';
        }
    }

}