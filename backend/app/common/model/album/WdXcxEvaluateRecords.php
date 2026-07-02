<?php

namespace app\common\model\album;


use app\common\model\user\WdXcxUser;
use think\Model;

class WdXcxEvaluateRecords extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_evaluate_records';
    protected $autoWriteTimestamp = true;

    public function getUserInfoAttr()
    {
        if($this->uid){
            $user = WdXcxUser::where('id', $this->uid)->find();
            if($user){
                $info = $user->getUserInfoShow($user->id);
                unset($info['mobile']);
                $info['join_days'] = floor((time() - $user->getData('create_time')) / (24 * 60 * 60));
                return $info;
            }else{
                $info = (new WdXcxUser())->getUserInfoShow($this->uid);
                unset($info['mobile']);
                $info['join_days'] = 0;
                return $info;
            }
        }else{
            $join_days = floor((time() - $this->getData('create_time')) / (24 * 60 * 60));
            return [
                'nickname' => $this->nickname,
                'avatar' => $this->avatar,
                'join_days' => $join_days,
            ];
        }
    }


    public function getAvatarAttr($value)
    {
        return $value ? remote(1, $value,1) : getLocalImage('/image/default/default_ticket_thumb.png');
    }

    public function setImagesAttr($value)
    {
        return $value ? remote(1, $value,2) : '';
    }


}