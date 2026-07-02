<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxUserIntegralRecord extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_integral_record';
    protected $autoWriteTimestamp = true;

    const INTEGRAL_CHANGE_DEL = 1; //消费
    const INTEGRAL_CHANGE_ADD = 2; //增加

    /**增加用户积分流水记录
     * @param $user
     * @param $data
     * @param $type
     * @return void
     */
    public function addRecord($user, $data, $type=self::INTEGRAL_CHANGE_DEL)
    {
        if(!empty($data['is_end'])){
            $new_integral = $data['change_integral'];
        }else{
            if($type == self::INTEGRAL_CHANGE_ADD){
                $new_integral = bcadd($user->integral, $data['change_integral']);
            }else{
                $new_integral = bcsub($user->integral, $data['change_integral']);
            }
        }
        $new_integral = $new_integral < 0 ? 0 : $new_integral;
        $this->insert([
            'uniacid' => $this->uniacid,
            'user_id' => $user->id,
            'change_type' => $type,
            'change_integral' => $data['change_integral'],
            'new_integral' => $new_integral,
            'order_id' => $data['order_id'],
            'message' => $data['message'],
            'change_source' => $data['change_source'],
            'create_time' => time(),
            'update_time' => time(),
        ]);
        $user->integral = $new_integral;
        $user->save();
        return $user->integral;
    }

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }
}