<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\common\service\ylb\YlbApiService;
use think\model\concern\SoftDelete;

class WdXcxUserBalanceRecord extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_balance_record';
    protected $autoWriteTimestamp = true;

    const BALANCE_CHANGE_DEL = 1; //消费
    const BALANCE_CHANGE_ADD = 2; //增加

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    /**增加用户余额改变记录
     * @param $user_leaguerID
     * @param $data
     * @param $type
     * @param $true_change 1 需要改变余额  0 不需要
     * @return mixed
     * @throws \cores\exception\BaseException
     */
    public function addRecord($user, $data, $type=self::BALANCE_CHANGE_DEL, $true_change=1)
    {
        if($data['change_price'] > 0){
            $order_id = $data['order_id'];
            $change_price = $type == self::BALANCE_CHANGE_DEL ? bcsub(0, $data['change_price'], 2) : $data['change_price'];
            //改变用户余额记录
            if($true_change){
                (new YlbApiService())->changeUserPrepaid($user->leaguer_id, $change_price, $order_id);
            }
            $new_price = WdXcxUser::where('id', $user->id)->find()->UserBalance;
            $new_price = str_replace(',', '', $new_price);
            $this->insert([
                'uniacid' => $this->uniacid,
                'user_id' => $data['user_id'],
                'change_type' => $type,
                'change_price' => $data['change_price'],
                'new_price' => $new_price,
                'order_id' => $data['order_id'],
                'message' => $data['message'],
                'change_source' => $data['change_source'],
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return $new_price;
        }
    }
}