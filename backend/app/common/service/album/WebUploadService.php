<?php

namespace app\common\service\album;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserAlbumPic;
use app\common\model\user\WdXcxUserAlbumUploadCode;
use app\common\model\user\WdXcxVipgrade;
use app\common\service\BaseService;
use app\common\service\JwtService;
use app\common\service\WxService;
use app\common\model\WdXcxBase;
use app\common\model\WdXcxPic;
use app\common\service\upload\UploadService;
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
        WdXcxUserAlbumUploadCode::ensureUploadEnabledColumn();
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
        (new WdXcxUser())->ensureUploadPasswordColumns();
        $user = WdXcxUser::where('id', $record->uid)->find();
        if(!$user){
            throwError('指定的上传码对应的用户不存在');
        }
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
            'upload_enabled' => (int)$record->upload_enabled,
            'access_enabled' => (int)$record->upload_enabled,
            'has_password' => $user->upload_pwd ? 1 : 0,
            'password_expire_time' => (int)$user->upload_pwd_expire_time,
            'password_expired' => $uploadPwdExpired ? 1 : 0,
            'id' => $folder->id,
            'image_base64' => $record->ewm_code,
            'folder_name' => $folder->folder_name,
            'owner_info' => $this->buildOwnerInfo($user),
            'owner_storage' => $this->buildOwnerStorage($user),
            'upload_policy' => $this->buildUploadPolicy($user),
            'product_info' => $this->buildProductInfo($folder),
        ];
        return $result;
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
        WdXcxUserAlbumUploadCode::ensureUploadEnabledColumn();
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
        (new WdXcxUser())->ensureUploadPasswordColumns();
        $user = WdXcxUser::where('id', $record->uid)->find();
        if(!$user){
            throwError('指定的上传码对应的用户不存在');
        }
        if((int)$record->upload_enabled !== 1){
            throwError('此产品协同编辑入口已关闭');
        }
        if($user->upload_pwd){
            if($this->isUploadPasswordExpired($user)){
                throwError('协同编辑密码已过期，请联系分享者更新密码');
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
        $role = 'cover';
        Db::startTrans();
        try{
            $data = (new UploadService($this->uniacid))->uploadImages([
                'files' => $params['files'],
                'flag' => 1,
                'gid' => $params['pid'],
                'uid' => $uid,
                'file_type' => $params['file_type'],
                'original_names' => $params['original_names'] ?? [],
            ]);
            $createdRelations = [];
            if(count($data) > 0){ //存入相关相册
                $folder_info = WdXcxAlbumFolder::where('id', $params['pid'])->find();
                $pic_album = [];
                $last_url = '';
                $uploadField = $this->normalizeProductUploadField($params['upload_field'] ?? '');
                $requestSort = $this->resolveUploadSort($params);
                $role = $uploadField === 'detail_chart' ? 'detail' : 'cover';
                foreach ($data as $item){
                    $imageDetection = $this->weChatImageValidation($item['url']);
                    if($imageDetection["data"] != 0){ // 图片涉黄了
                        throwError("图片检测不通过，请重新上传");
                    }
                    $pic_album[] = [
                        'sort' => $requestSort,
                        'uniacid' => 1,
                        'user_id' => $uid,
                        'pic_id' => $item['pid'],
                        'folder_id' => $params['pid'],
                        'set_top_time' => time(),
                        'create_time' => time(),
                        'update_time' => time(),
                        'upload_date' => date('Y-m-d'),
                        'upload_field' => $uploadField,
                    ];
                    $last_url = $item['url'];
                }
                $syncStartTime = time() - 5;
                WdXcxUserAlbumPic::insertAll($pic_album);
                $createdRelations = WdXcxUserAlbumPic::where('folder_id', $params['pid'])
                    ->whereIn('pic_id', array_column($pic_album, 'pic_id'))
                    ->where('create_time', '>=', $syncStartTime)
                    ->with(['picture'])
                    ->order('id asc')
                    ->select();
                $this->normalizeCreatedRelationSorts($createdRelations);
                if($need_check){
                    $folder_info->check_status = 0;
                    $folder_info->save();
                }else{
                    if($role === 'cover'){
                        $this->updateParentThumbs($folder_info->id, $last_url);
                    }
                }
            }
        }catch (\Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
        if (!empty($createdRelations)) {
            $bridge = new AiResourceBridgeService($this->app);
            foreach ($createdRelations as $relation) {
                $bridge->safeSyncAlbumRelation($folder_info->uid, $relation, $role);
            }
        }
        return [
            'msg' => $need_check == 1 ? '上传成功，请等待审核' : '上传成功',
            'data' => $data,
        ];
    }

    private function normalizeCreatedRelationSorts($relations)
    {
        foreach ($relations as $relation) {
            if (!$relation || (int)($relation->id ?? 0) <= 0 || (int)($relation->sort ?? 0) > 0) {
                continue;
            }
            $sort = (int)$relation->id;
            WdXcxUserAlbumPic::where('id', $relation->id)->update(['sort' => $sort]);
            $relation->sort = $sort;
        }
    }

    private function resolveUploadSort($params)
    {
        $batchStartedAt = (int)($params['batch_started_at'] ?? 0);
        $sortOrder = (int)($params['sort_order'] ?? 0);
        if ($batchStartedAt > 0 && $sortOrder > 0) {
            $sort = $batchStartedAt + min($sortOrder, 999);
            return max(1, min($sort, 2147483647));
        }
        return 0;
    }

    private function normalizeProductUploadField($value)
    {
        $value = strtolower(trim((string)$value));
        if (in_array($value, ['detail', 'detail_chart', 'detailchart', 'detail_pic', 'detail_pic_ids', '2'], true)) {
            return 'detail_chart';
        }
        return 'color_chart';
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

    private function buildOwnerInfo($user)
    {
        return [
            'id' => (int)$user->id,
            'nickname' => (string)($user->nickname ?: '分享者'),
            'company_name' => (string)($user->company_name ?: ''),
            'display_name' => (string)($user->company_name ?: ($user->nickname ?: '分享者')),
            'avatar' => (string)($user->avatar ?: ''),
            'company_logo' => (string)($user->company_logo ?: ''),
            'company_desc' => (string)($user->company_desc ?: ''),
        ];
    }

    private function buildOwnerStorage($user)
    {
        $vipGradeInfo = $user->VipGradeInfo;
        $capacityMb = isset($vipGradeInfo['space_size']) ? (float)$vipGradeInfo['space_size'] : 0;
        $capacityBytes = max(0, (int)round($capacityMb * 1024 * 1024));
        $usedBytes = (int)WdXcxPic::where('uid', $user->id)->sum('size');
        $remainingBytes = $capacityBytes > 0 ? max(0, $capacityBytes - $usedBytes) : 0;
        $usedPercent = $capacityBytes > 0 ? round(min(100, max(0, $usedBytes / $capacityBytes * 100)), 2) : 0;
        return [
            'capacity_bytes' => $capacityBytes,
            'used_bytes' => $usedBytes,
            'remaining_bytes' => $remainingBytes,
            'capacity_text' => $this->formatBytes($capacityBytes),
            'used_text' => $this->formatBytes($usedBytes),
            'remaining_text' => $this->formatBytes($remainingBytes),
            'used_percent' => $usedPercent,
        ];
    }

    private function buildUploadPolicy($user)
    {
        $concurrencyLimit = $this->getUploadConcurrencyLimit($user);
        return [
            'concurrency' => $concurrencyLimit,
            'upload_concurrency' => $concurrencyLimit,
            'concurrency_limit' => $concurrencyLimit,
            'single_file_limit_mb' => (int)$user->TrueUploadSize,
            'traffic_limit_bytes' => 0,
            'traffic_used_bytes' => 0,
            'traffic_remaining_bytes' => 0,
            'traffic_limit_text' => '不限量',
            'traffic_used_text' => '0MB',
            'traffic_remaining_text' => '不限量',
            'traffic_used_percent' => 0,
        ];
    }

    private function getUploadConcurrencyLimit($user)
    {
        $vipGradeInfo = $user->VipGradeInfo;
        $gradeLevel = (int)($vipGradeInfo['grade_level'] ?? 0);
        if ($gradeLevel <= 0) {
            return 1;
        }

        $endTime = $vipGradeInfo['end_time'] ?? 0;
        if ($endTime && strtotime($endTime . ' 23:59:59') < time()) {
            return 1;
        }

        $editorNumber = (int)WdXcxVipgrade::where('grade_level', $gradeLevel)
            ->where('uniacid', $user->uniacid)
            ->value('editor_number');
        if ($editorNumber <= 0) {
            $editorNumber = (int)WdXcxVipgrade::where('grade_level', $gradeLevel)
                ->value('editor_number');
        }

        return max(1, min($editorNumber ?: 1, 10));
    }

    private function buildProductInfo($folder)
    {
        return [
            'id' => (int)$folder->id,
            'name' => (string)$folder->folder_name,
            'folder_name' => (string)$folder->folder_name,
            'desc' => (string)($folder->folder_desc ?: ''),
            'cover' => (string)($folder->new_thumb ?: ''),
        ];
    }

    private function formatBytes($bytes)
    {
        $bytes = max(0, (float)$bytes);
        if ($bytes >= 1024 * 1024 * 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / 1024 / 1024 / 1024 / 1024, 2, '.', ''), '0'), '.') . 'TB';
        }
        if ($bytes >= 1024 * 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / 1024 / 1024 / 1024, 2, '.', ''), '0'), '.') . 'GB';
        }
        if ($bytes >= 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / 1024 / 1024, 2, '.', ''), '0'), '.') . 'MB';
        }
        if ($bytes >= 1024) {
            return rtrim(rtrim(number_format($bytes / 1024, 2, '.', ''), '0'), '.') . 'KB';
        }
        return (int)$bytes . 'B';
    }





}
