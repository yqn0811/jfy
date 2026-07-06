<?php

namespace app\common\service\album;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserAlbumPic;
use app\common\model\user\WdXcxUserAlbumUploadCode;
use app\common\service\BaseService;
use app\common\service\bridge\JiafangyunEntitlementSyncService;
use app\common\service\JwtService;
use app\common\service\WxService;
use app\index\model\WdXcxBase;
use app\index\model\WdXcxPic;
use app\index\service\upload\UploadService;
use think\App;
use think\facade\Db;

class WebUploadService extends BaseService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * 获取上传文件夹信息
     * @param $param
     * @return array
     */
    public function getWebAlbumInfo($param)
    {
        $record = WdXcxUserAlbumUploadCode::where('upload_code', $param['code'])->find();
        if(!$record){
            throwError('指定的上传码不存在');
        }
        $folder = WdXcxAlbumFolder::where('id', $record['fid'])->find();
        if(!$folder){
            throwError('指定的上传码对应的相册不存在');
        }
        if($folder->folder_type != 2){
            throwError('当前文件夹不可以上传照片');
        }
        $user = WdXcxUser::where('id', $record->uid)->find();
        if(!$user){
            throwError('指定的上传码对应的用户不存在');
        }
        (new WdXcxUser())->ensureUploadPasswordColumns();
        $syncedVipGradeInfo = (new JiafangyunEntitlementSyncService($this->app))->syncUserQuietly($user->id);
        $uploadPwdExpired = $this->isUploadPasswordExpired($user);
        if(!$record->ewm_code){
            try {
                $file_path = public_path().'image/ewm';
                $file_name = 'album_folder_'.$record->fid.'.jpg';
                (new WxService())->getWxQrcode([
                    'path' => 'pagesOther/imgBook/imgBook?id='.$record->fid.'&folder_name='.$folder->folder_name.'&folder_type=2&visit_times='.$folder->visit_times,
                    'filename' => $file_name,
                    'filepath' => $file_path,
                ]);
                $qrcode_path = '/image/ewm/'.$file_name;
                $ewm_code = file_get_contents(public_path().$qrcode_path);
                $record->ewm_code = base64_encode($ewm_code);
                $record->save();
            }catch (Exception $exception){
                throwError($exception->getMessage());
            }
        }
        $result = [
            'content' => '把该链接分享给好友，即可多人一起上传哦',
            'has_password' => $user->upload_pwd ? 1 : 0,
            'password_expire_time' => (int)$user->upload_pwd_expire_time,
            'password_expired' => $uploadPwdExpired ? 1 : 0,
            'id' => $folder->id,
            'image_base64' => $record->ewm_code,
            'folder_name' => $folder->folder_name,
            'owner_info' => $this->getOwnerInfo($user),
            'product_info' => $this->getProductInfo($folder),
            'owner_storage' => $this->getOwnerStorageInfo($user, $syncedVipGradeInfo),
            'upload_policy' => $this->getUploadPolicyInfo($user, $syncedVipGradeInfo),
        ];
        return $result;
    }

    private function getOwnerInfo($user)
    {
        $companyName = trim((string)($user->company_name ?: $user->home_share_title ?: ''));
        $nickname = trim((string)($user->nickname ?: ''));
        $displayName = $companyName ?: ($nickname ?: '分享者');
        $avatar = $user->avatar;
        $desc = trim((string)($user->company_desc ?: $user->user_desc ?: $user->home_share_desc ?: ''));

        return [
            'id' => (int)$user->id,
            'display_name' => $displayName,
            'company_name' => $companyName,
            'nickname' => $nickname,
            'avatar' => $avatar,
            'company_logo' => $user->company_logo,
            'company_desc' => $desc,
        ];
    }

    private function getProductInfo($folder)
    {
        $name = trim((string)$folder->folder_name);
        if ($name === '') {
            $name = '未命名产品 #' . $folder->id;
        }

        return [
            'id' => (int)$folder->id,
            'name' => $name,
            'folder_name' => (string)$folder->folder_name,
            'desc' => (string)($folder->folder_desc ?: ''),
            'cover' => $folder->new_thumb,
        ];
    }

    private function getUploadPolicyInfo($user, $syncedVipGradeInfo = null)
    {
        $vipGradeInfo = $user->VipGradeInfo;
        if (is_array($syncedVipGradeInfo)) {
            $vipGradeInfo = array_merge($vipGradeInfo, $syncedVipGradeInfo);
        }
        $concurrency = (int)($vipGradeInfo['upload_concurrency'] ?? ($vipGradeInfo['concurrency_limit'] ?? 0));
        if ($concurrency <= 0) {
            $concurrency = $this->defaultUploadConcurrency((int)($vipGradeInfo['grade_level'] ?? $user->vip_grade));
        }

        return [
            'concurrency' => $this->normalizeUploadConcurrency($concurrency),
            'max_files_per_batch' => 200,
            'grade_level' => (int)($vipGradeInfo['grade_level'] ?? $user->vip_grade),
            'grade_name' => (string)($vipGradeInfo['grade_name'] ?? ''),
        ];
    }

    private function defaultUploadConcurrency($gradeLevel)
    {
        if ($gradeLevel >= 4) {
            return 5;
        }
        if ($gradeLevel >= 3) {
            return 3;
        }
        if ($gradeLevel >= 2) {
            return 2;
        }
        return 1;
    }

    private function normalizeUploadConcurrency($value)
    {
        return max(1, min(8, (int)$value));
    }

    private function getOwnerStorageInfo($user, $syncedVipGradeInfo = null)
    {
        $vipGradeInfo = $user->VipGradeInfo;
        if (is_array($syncedVipGradeInfo)) {
            $vipGradeInfo = array_merge($vipGradeInfo, $syncedVipGradeInfo);
        }
        $capacityBytes = (int)($vipGradeInfo['resource_storage']['capacity_bytes'] ?? 0);
        if ($capacityBytes <= 0 && !empty($vipGradeInfo['space_size'])) {
            $capacityBytes = (int)$vipGradeInfo['space_size'] * 1024 * 1024;
        }
        $usedBytes = (int)($vipGradeInfo['resource_storage']['used_bytes'] ?? 0);
        $localPicSize = (int)WdXcxPic::where('uid', $user->id)->sum('size');
        if ($usedBytes <= 0) {
            $usedBytes = $localPicSize;
        }
        $remainingBytes = $capacityBytes > 0 ? max(0, $capacityBytes - $usedBytes) : 0;
        $usedPercent = $capacityBytes > 0 ? round(min(100, ($usedBytes / $capacityBytes) * 100), 2) : 0;

        return [
            'used_bytes' => $usedBytes,
            'capacity_bytes' => $capacityBytes,
            'remaining_bytes' => $remainingBytes,
            'used_text' => $this->formatStorageBytes($usedBytes),
            'capacity_text' => $this->formatStorageBytes($capacityBytes),
            'remaining_text' => $this->formatStorageBytes($remainingBytes),
            'used_percent' => $usedPercent,
            'local_pic_used_bytes' => $localPicSize,
        ];
    }

    private function formatStorageBytes($bytes)
    {
        $bytes = (int)$bytes;
        if ($bytes <= 0) {
            return '0M';
        }
        if ($bytes >= 1024 * 1024 * 1024 * 1024) {
            return $this->formatStorageNumber($bytes / 1024 / 1024 / 1024 / 1024) . 'T';
        }
        if ($bytes >= 1024 * 1024 * 1024) {
            return $this->formatStorageNumber($bytes / 1024 / 1024 / 1024) . 'G';
        }
        return $this->formatStorageNumber($bytes / 1024 / 1024) . 'M';
    }

    private function formatStorageNumber($value)
    {
        $text = number_format((float)$value, 2, '.', '');
        return rtrim(rtrim($text, '0'), '.');
    }

    /**获取上传token
     * @param $param
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWebAlbumUploadToken($param)
    {
        $record = WdXcxUserAlbumUploadCode::where('upload_code', $param['code'])->find();
        if(!$record){
            throwError('指定的上传码不存在');
        }
        $folder = WdXcxAlbumFolder::where('id', $record['fid'])->find();
        if(!$folder){
            throwError('指定的上传码对应的相册不存在');
        }
        if($folder->folder_type != 2){
            throwError('当前文件夹不可以上传照片');
        }
        $user = WdXcxUser::where('id', $record->uid)->find();
        if(!$user){
            throwError('指定的上传码对应的用户不存在');
        }
        (new WdXcxUser())->ensureUploadPasswordColumns();
        if($user->upload_pwd){
            if($this->isUploadPasswordExpired($user)){
                throwError('上传密码已过期，请联系分享者更新密码');
            }
            if(empty($param['password'])){
                throwError('请填写密码');
            }
            if($param['password'] != $user->upload_pwd){
                throwError("密码填写错误\n1、如果是你的相册，请打开小程序【我的-修改资料】确认密码。\n2、如果是他人的相册，请咨询相册所有者最新的密码。");
            }
        }
        if($folder->uid != $user->id){
            if(!$folder->checkFolderRule($user->id)){
                throwError('您没有权限操作此文件夹');
            }
        }
        $result['user_uuid'] = $user->user_uuid;
        $result['user_id'] = $user->id;
        $result['scope'] = 'web_album_upload';
        $result['fid'] = (int)$folder->id;
        $result['owner_id'] = (int)$folder->uid;
        $token = JwtService::createWebToken($result);
        return [
            'token' => $token,
            'user_id' => $user->user_uuid,
        ];

    }

    public function getUploadFolderInfo($param, $uid, $tokenFid = 0)
    {
        if($tokenFid && (int)$param['fid'] !== (int)$tokenFid){
            throwError('上传凭证与相册不匹配');
        }
        $folder = WdXcxAlbumFolder::where('id', $param['fid'])->find();
        if(!$folder){
            throwError('指定的上传码对应的相册不存在');
        }
        if($folder->folder_type != 2){
            throwError('当前文件夹不可以上传照片');
        }
        $user = WdXcxUser::where('id', $uid)->find();
        if(!$user){
            throwError('指定的上传码对应的用户不存在');
        }
        if($folder->uid != $uid){
            if(!$folder->checkFolderRule($uid)){
                throwError('您没有权限操作此文件夹');
            }
        }
        $result = [
            'err_title' => '你的云空间不足',
            'desc' => '',
            'free_size' => $user->UserCanUserPicSize*1024,
            'upload_limit' => $user->TrueUploadSize,
        ];
        return $result;

    }

    public function uploadFileAlbum($params, $uid)
    {
        $need_check = WdXcxBase::where('uniacid', 1)->value('pic_check');
        $uploadField = isset($params['upload_field']) && $params['upload_field'] === 'detail_pic_ids' ? 'detail_pic_ids' : 'pic_ids';
        $shouldSyncProductPictures = false;
        $folder_info = WdXcxAlbumFolder::where('id', $params['pid'])->find();
        if(!$folder_info){
            throwError('指定的上传码对应的相册不存在');
        }
        $ownerUid = (int)($params['owner_uid'] ?? $folder_info->uid);
        if($ownerUid <= 0 || $ownerUid !== (int)$folder_info->uid){
            throwError('上传凭证与相册主不匹配');
        }
        Db::startTrans();
        try{
            $data = (new UploadService($this->uniacid))->uploadImages([
                'files' => $params['files'],
                'flag' => 1,
                'gid' => $params['pid'],
                'uid' => $ownerUid,
                'file_type' => $params['file_type'],
                'original_names' => $params['original_names'] ?? [],
            ]);
            $createdRelations = [];
            if(count($data) > 0){ //存入相关相册
                $pic_album = [];
                $last_url = '';
                $originalNames = $params['original_names'] ?? [];
                foreach ($data as $index => $item){
                    $imageDetection = $this->weChatImageValidation($item['url']);
                    if($imageDetection["data"] != 0){ // 图片涉黄了
                        throwError("图片检测不通过，请重新上传");
                    }
                    $pic_album[] = [
                        'uniacid' => 1,
                        'user_id' => $ownerUid,
                        'pic_id' => $item['pid'],
                        'folder_id' => $params['pid'],
                        'set_top_time' => time(),
                        'create_time' => time(),
                        'update_time' => time(),
                        'upload_date' => date('Y-m-d'),
                        'upload_field' => $uploadField,
                    ];
                    $originalName = $item['pic_name'] ?? '';
                    if (!$originalName && !empty($originalNames[$index])) {
                        $originalName = $originalNames[$index];
                    }
                    if (!$originalName && !empty($originalNames['default'])) {
                        $originalName = $originalNames['default'];
                    }
                    if ($originalName) {
                        \app\index\model\WdXcxPic::where('id', $item['pid'])->update([
                            'pic_name' => (string)$originalName
                        ]);
                    }
                    $last_url = $item['url'];
                }
                $syncStartTime = time() - 5;
                WdXcxUserAlbumPic::insertAll($pic_album);
                $shouldSyncProductPictures = $this->appendProductPictureIds($folder_info, array_column($pic_album, 'pic_id'), $uploadField);
                $folder_info = WdXcxAlbumFolder::where('id', $params['pid'])->find();
                $createdRelations = WdXcxUserAlbumPic::where('folder_id', $params['pid'])
                    ->whereIn('pic_id', array_column($pic_album, 'pic_id'))
                    ->where('create_time', '>=', $syncStartTime)
                    ->with(['picture'])
                    ->order('id desc')
                    ->select();
                if($need_check){
                    $folder_info->check_status = 0;
                    $folder_info->save();
                }else{
                    if($params['file_type'] == 1){
                        $this->updateParentThumbs($folder_info->id, $last_url);
                    }
                }
            }
        }catch (\Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
        if ($shouldSyncProductPictures) {
            (new AiResourceBridgeService($this->app))->safeSyncProductPictures($folder_info->uid, WdXcxAlbumFolder::where('id', $folder_info->id)->find());
        }
        if (!empty($createdRelations)) {
            $bridge = new AiResourceBridgeService($this->app);
            foreach ($createdRelations as $relation) {
                $bridge->safeSyncAlbumRelation($folder_info->uid, $relation, 'album');
            }
        }
        return [
            'msg' => $need_check == 1 ? '上传成功，请等待审核' : '上传成功',
            'data' => $data,
        ];
    }

    private function appendProductPictureIds($folder, $picIds, $field)
    {
        if(!$folder || !in_array($field, ['pic_ids', 'detail_pic_ids'])){
            return false;
        }
        $oldIds = $this->normalizeIdList($folder->getData($field) ?? '');
        $newIds = $this->normalizeIdList($picIds);
        if(empty($newIds)){
            return false;
        }
        $merged = array_values(array_unique(array_merge($oldIds, $newIds)));
        $folder->save([
            $field => implode(',', $merged)
        ]);
        return true;
    }

    private function normalizeIdList($value)
    {
        if(is_array($value)){
            $items = $value;
        }else{
            $text = trim((string)$value);
            if($text === ''){
                return [];
            }
            $items = explode(',', $text);
        }
        $ids = [];
        foreach($items as $item){
            $id = (int)$item;
            if($id > 0){
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    /**更新父级文件夹的缩略图
     * @param $folderId
     * @param $thumbPath
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateParentThumbs($folderId, $thumbPath)
    {
        $folder = WdXcxAlbumFolder::where('id', $folderId)->find();
        if (!$folder) {
            return;
        }

        // 更新当前文件夹的缩略图
        $folder->new_thumb = $thumbPath;
        if($thumbPath){
            $folder->new_thumb_time = time();
        }
        $folder->save();

        // 如果有父级文件夹，则递归更新
        if ($folder->pid > 0) {
            $this->updateParentThumbs($folder->pid, $thumbPath);
        }
    }

    private function isUploadPasswordExpired($user)
    {
        $expireTime = isset($user->upload_pwd_expire_time) ? (int)$user->upload_pwd_expire_time : 0;
        return $expireTime > 0 && $expireTime < time();
    }





}
