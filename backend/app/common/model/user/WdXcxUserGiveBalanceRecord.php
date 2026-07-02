<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\common\service\ylb\YlbApiService;
use think\model\concern\SoftDelete;

class WdXcxUserGiveBalanceRecord extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_give_balance_record';
    protected $autoWriteTimestamp = true;

    const BALANCE_CHANGE_DEL = 1; //消费
    const BALANCE_CHANGE_ADD = 2; //增加

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    /**改变用户赠送余额记录
     * @param $user_leaguerID
     * @param $data
     * @param $type
     * @return mixed
     * @throws \cores\exception\BaseException
     */
    public function addRecord($user, $data, $type=self::BALANCE_CHANGE_DEL)
    {
        if(!empty($data['is_end'])){
            $new_balance = bcadd($data['change_price'], 0, 2);
        }else{
            if($type == self::BALANCE_CHANGE_ADD){
                $new_balance = bcadd($user->give_balance, $data['change_price'], 2);
            }else{
                $new_balance = bcsub($user->give_balance, $data['change_price'], 2);
            }
        }
        $new_balance = $new_balance < 0 ? 0 : $new_balance;
        $this->insert([
            'uniacid' => $this->uniacid,
            'user_id' => $user->id,
            'change_type' => $type,
            'change_price' => $data['change_price'],
            'new_price' => $new_balance,
            'order_id' => $data['order_id'],
            'message' => $data['message'],
            'change_source' => $data['change_source'],
            'create_time' => time(),
            'update_time' => time(),
        ]);
        $user->give_balance = $new_balance;
        $user->save();
        return $user->give_balance;
    }
}