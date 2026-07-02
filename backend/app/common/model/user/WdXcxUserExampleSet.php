<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\index\model\WdXcxPic;
use think\model\concern\SoftDelete;

class WdXcxUserExampleSet extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_example_set';
    protected $autoWriteTimestamp = true;

    /**获取基础设置
     * @param $folder_id
     * @param $uid
     * @return WdXcxUserExampleSet|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSet($folder_id, $uid)
    {
        $has = $this->where('folder_id', $folder_id)
            ->where('uid', $uid)
            ->field('subscribe_status, pic_show, folder_show')
            ->find();
        if(!$has){
            $this->insert([
                'folder_id' => $folder_id,
                'uid' => $uid,
                'subscribe_status' => 0,
                'pic_show' => 1,
                'folder_show' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return $this->where('folder_id', $folder_id)
                ->where('uid', $uid)
                ->field('subscribe_status, pic_show, folder_show')
                ->find();
        }
        return $has;
    }

    /**保存设置
     * @param $param
     * @param $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveSet($param, $uid)
    {
        $set = $this->getSet($param['folder_id'], $uid);
        isset($param['subscribe_status']) && $set->subscribe_status = $param['subscribe_status'];
        isset($param['pic_show']) && $set->pic_show = $param['pic_show'];
        isset($param['folder_show']) && $set->folder_show = $param['folder_show'];
        return $set->save();
    }

    /**获取所有关注
     * @param $uid
     * @return array
     */
    public function getAllFollow($uid)
    {
        return $this->where('uid', $uid)->column('folder_id');
    }


}