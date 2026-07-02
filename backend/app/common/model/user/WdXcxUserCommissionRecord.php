<?php

namespace app\common\model\user;

use app\common\model\BaseModel;

class WdXcxUserCommissionRecord extends BaseModel
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_commission_record';
    protected $autoWriteTimestamp = true;

    const COMMISSION_CHANGE_DEL = 1; //减少
    const COMMISSION_CHANGE_ADD = 2; //增加

    const CHANGE_SOURCE_WITHDRAWAL = 'withdrawal'; //提现
    const CHANGE_SOURCE_REFUSE_WITHDRAWAL = 'refuse_withdrawal'; //提现拒绝返回

    /**佣金记录
     * @param $user_id int 用户id
     * @param $data
     * @param $type
     * @return int|string
     */
    public function addRecord($user_id, $data, $type=self::COMMISSION_CHANGE_DEL)
    {
        $user = WdXcxUser::where('id', $user_id)->lock(true)->find();
        if(!empty($data['is_end'])){
            $new_commission = bcadd($data['change_price'], 0, 2);
        }else{
            if($type == self::COMMISSION_CHANGE_ADD){
                $new_commission = bcadd($user->current_commission, $data['change_price'], 2);
                $user->total_commission = bcadd($user->total_commission, $data['change_price'], 2);
            }else{
                $new_commission = bcsub($user->current_commission, $data['change_price'], 2);
            }
        }
        $new_commission = $new_commission < 0 ? 0 : $new_commission;
        $this->insert([
            'uniacid' => $this->uniacid,
            'user_id' => $user->id,
            'change_type' => $type,
            'change_price' => $data['change_price'],
            'new_price' => $new_commission,
            'order_id' => $data['order_id'],
            'message' => $data['message'],
            'change_source' => $data['change_source'],
            'create_time' => time(),
            'update_time' => time(),
        ]);
        $user->current_commission = $new_commission;
        $user->save();
        return $user->current_commission;
    }

}