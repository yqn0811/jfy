<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\index\model\WdXcxPic;
use think\model\concern\SoftDelete;

class WdXcxUserAlbumPic extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_album_pic';
    protected $autoWriteTimestamp = true;

    public function picture()
    {
        return $this->hasOne(WdXcxPic::class, 'id', 'pic_id');
    }

    public function getUserInfoAttr()
    {
        $user = WdXcxUser::where('id', $this->user_id)->find();
        if($user){
            $info = $user->getUserInfoShow($user->id);
            unset($info['mobile']);
            return $info;
        }else{
            $info = (new WdXcxUser())->getUserInfoShow($this->user_id);
            unset($info['mobile']);
            return $info;
        }
    }

}