<?php

namespace app\common\model\album;

use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserAlbumPic;
use app\index\model\WdXcxPic;
use think\Model;
use think\model\concern\SoftDelete;

class WdXcxAlbumFolder extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_album_folder';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'upload_field' => 'array',
    ];

    public function getNewThumbAttr($value)
    {
        if($this->folder_type == 1){
            return getLocalImage('/image/static/folder.png');
        }else{
            $picIds = $this->pic_ids ? array_values(array_filter(array_map('intval', explode(',', (string)$this->pic_ids)))) : [];
            if (!empty($picIds)) {
                $resourcePic = WdXcxPic::whereIn('id', $picIds)
                    ->field('id,imgurl,pic_name,uniacid,file_type')
                    ->orderRaw('FIELD(id, ' . implode(',', $picIds) . ')')
                    ->find();
                if ($resourcePic && method_exists($resourcePic, 'isImportedResourcePicture') && $resourcePic->isImportedResourcePicture()) {
                    return $resourcePic->TruePic;
                }
            }
            if ($value) {
                return WdXcxPic::normalizePictureUrl($value, 1, 1);
            }
            $pic = WdXcxUserAlbumPic::where('folder_id', $this->id)->order('id asc')->find();
            if($pic && $pic->picture){
                return $pic->picture->TruePic;
            }
            return '';
        }
//        return $value ? remote(1, $value, 1) : '';
    }

    public function setNewThumbAttr($value)
    {
        if (strpos((string)$value, '//') === 0) {
            return removePicStyle('https:' . $value);
        }
        if (WdXcxPic::isSchemeLessHttpUrl($value)) {
            return removePicStyle('https://' . ltrim($value, '/'));
        }
        if (WdXcxPic::isHttpUrl($value)) {
            return removePicStyle($value);
        }
        return $value ? remote(1, $value, 2) : '';
    }

    public function getSonCountAttr()
    {
        if($this->folder_type == 1){
            return $this->where('pid', $this->id)
                ->where('uid', $this->uid)
                ->where('folder_type', 1)
                ->count();
        }else{
            return WdXcxUserAlbumPic::where('folder_id', $this->id)->count();
        }
    }

    public function getUserInfoAttr()
    {
        $user = WdXcxUser::where('id', $this->uid)->find();
        if($user){
            $info = $user->getUserInfoShow($user->id);
            unset($info['mobile']);
            return $info;
        }else{
            $info = (new WdXcxUser())->getUserInfoShow($this->uid);
            unset($info['mobile']);
            return $info;
        }
    }

    /**获取所有父级id，包含自己
     * @return array
     */
    public function getParentIdsAttr()
    {
        if($this->pid == 0){
            $parent_ids = [];
        }else{
            $parent_ids = $this->getParentIdsData($this->pid);
            $parent_ids[] = $this->pid;
        }
        $parent_ids[] = $this->id;
        return $parent_ids;
    }

    public function getParentIdsData($folderId)
    {
        $parentIds = [];
        $folder = WdXcxAlbumFolder::where('id', $folderId)->find();
        if ($folder && $folder->pid > 0) {
            $parentIds[] = $folder->pid;
            $parentIds = array_merge($parentIds, $this->getParentIdsData($folder->pid));
        }
        return $parentIds;
    }

    /**检查用户是否有此文件夹权限
     * @param $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkFolderRule($uid)
    {
        $has_bind = WdXcxAlbumShareBind::where('bind_uid', $uid)
            ->whereIn('fid', $this->ParentIds)
            ->find();
        return $has_bind ? true : false;
    }

    public function getFolderLevalAttr()
    {
        return $this->getFolderLevelData($this->id);
    }

    /**
     * 递归获取文件夹层级
     * @param $folderId
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFolderLevelData($folderId)
    {
        $folder = WdXcxAlbumFolder::where('id', $folderId)->find();
        if ($folder && $folder->pid > 0) {
            return 1 + $this->getFolderLevelData($folder->pid);
        }
        return 1;
    }

}
