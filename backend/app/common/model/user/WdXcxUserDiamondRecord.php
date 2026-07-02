<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxUserDiamondRecord extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_diamond_record';
    protected $autoWriteTimestamp = true;

    const DIAMOND_CHANGE_DEL = 1; //消费
    const DIAMOND_CHANGE_ADD = 2; //增加

    /**增加用户钻石流水记录
     * @param $user
     * @param $data
     * @param $type
     * @return void
     */
    public function addRecord($user, $data, $type=self::DIAMOND_CHANGE_DEL)
    {
        if(!empty($data['is_end'])){
            $new_diamond = $data['change_diamond'];
        }else{
            if($type == self::DIAMOND_CHANGE_ADD){
                $new_diamond = bcadd($user->diamond, $data['change_diamond']);
            }else{
                $new_diamond = bcsub($user->diamond, $data['change_diamond']);
            }
        }
        $new_diamond = $new_diamond < 0 ? 0 : $new_diamond;
        $this->insert([
            'uniacid' => $this->uniacid,
            'user_id' => $user->id,
            'change_type' => $type,
            'change_diamond' => $data['change_diamond'],
            'new_diamond' => $new_diamond,
            'order_id' => $data['order_id'],
            'message' => $data['message'],
            'change_source' => $data['change_source'],
            'create_time' => time(),
            'update_time' => time(),
        ]);
        $user->diamond = $new_diamond;
        $user->save();
        return $user->diamond;
    }

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }
}