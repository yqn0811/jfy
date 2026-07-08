<?php

namespace app\common\service\album;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\album\WdXcxAlbumShareBind;
use app\common\model\album\WdXcxProductCategoryBind;
use app\common\model\album\WdXcxAlbumShareRecord;
use app\common\model\album\WdXcxAlbumVisitRecord;
use app\common\model\album\WdXcxEvaluateRecords;
use app\common\model\album\WdXcxVisitFolderPwd;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserAlbumPic;
use app\common\model\user\WdXcxUserAlbumUploadCode;
use app\common\model\user\WdXcxUserCollectPics;
use app\common\model\user\WdXcxUserCollectAlbums;
use app\common\model\user\WdXcxUserReportAlbum;
use app\common\model\user\WdXcxVipgrade;
use app\common\service\BaseService;
use app\index\model\WdXcxBase;
use app\index\model\WdXcxPic;
use app\index\service\upload\UploadService;
use cores\utils\Utils;
use think\App;
use think\cache\driver\Redis;
use think\Collection;
use think\facade\Db;
use think\facade\Log;

class AlbumService extends BaseService
{
    private $pendingBridgeDeletes = [];

    public function __construct(App $app)
    {
        parent::__construct($app);
        }


    private function ensureBindUseridColumn()
    {
        self::ensureColumnExists(
            'wd_xcx_product_category_bind',
            'userid',
            "ALTER TABLE `wd_xcx_product_category_bind` ADD COLUMN `userid` int(11) NOT NULL DEFAULT 0 AFTER `category_id`"
        );
        self::ensureIndexExists(
            'wd_xcx_product_category_bind',
            'idx_userid',
            "ALTER TABLE `wd_xcx_product_category_bind` ADD INDEX `idx_userid`(`userid`)"
        );
    }

    private function ensureSetTopColumns()
    {
        self::ensureColumnExists(
            'wd_xcx_album_folder',
            'set_top',
            "ALTER TABLE `wd_xcx_album_folder` ADD COLUMN `set_top` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否置顶 1是 0否'"
        );
        self::ensureIndexExists(
            'wd_xcx_album_folder',
            'idx_set_top',
            "ALTER TABLE `wd_xcx_album_folder` ADD INDEX `idx_set_top`(`set_top`)"
        );
        self::ensureColumnExists(
            'wd_xcx_album_folder',
            'set_top_time',
            "ALTER TABLE `wd_xcx_album_folder` ADD COLUMN `set_top_time` int(11) NOT NULL DEFAULT 0 COMMENT '置顶时间'"
        );
    }

    public static function ensureProductStatusColumns()
    {
        self::ensureColumnExists(
            'wd_xcx_album_folder',
            'is_hot',
            "ALTER TABLE `wd_xcx_album_folder` ADD COLUMN `is_hot` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否热门产品 1是 0否'"
        );
        self::ensureIndexExists(
            'wd_xcx_album_folder',
            'idx_is_hot',
            "ALTER TABLE `wd_xcx_album_folder` ADD INDEX `idx_is_hot`(`is_hot`)"
        );
        self::ensureColumnExists(
            'wd_xcx_album_folder',
            'hide_detail_pictures',
            "ALTER TABLE `wd_xcx_album_folder` ADD COLUMN `hide_detail_pictures` tinyint(1) NOT NULL DEFAULT 0 COMMENT '分享访客隐藏详情图 1隐藏 0展示' AFTER `detail_pic_ids`"
        );
    }

    private static function ensureColumnExists($table, $column, $alterSql)
    {
        $hasColumn = Db::query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        if ($hasColumn) {
            return;
        }
        try {
            Db::execute($alterSql);
        } catch (\Throwable $e) {
            if (!self::isDuplicateSchemaError($e)) {
                throw $e;
            }
        }
    }

    private static function ensureIndexExists($table, $index, $alterSql)
    {
        $hasIndex = Db::query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index}'");
        if ($hasIndex) {
            return;
        }
        try {
            Db::execute($alterSql);
        } catch (\Throwable $e) {
            if (!self::isDuplicateSchemaError($e)) {
                throw $e;
            }
        }
    }

    private static function isDuplicateSchemaError(\Throwable $e)
    {
        $message = $e->getMessage();
        return strpos($message, 'SQLSTATE[42S21]') !== false
            || strpos($message, 'SQLSTATE[42000]') !== false
            || stripos($message, 'Duplicate column') !== false
            || stripos($message, 'Duplicate key name') !== false
            || stripos($message, 'Column already exists') !== false;
    }

    private function ensureBindTable()
    {
        $tables = Db::query("SHOW TABLES LIKE 'wd_xcx_product_category_bind'");
        if (!$tables) {
            Db::execute("
                CREATE TABLE IF NOT EXISTS `wd_xcx_product_category_bind` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `uniacid` int(11) NOT NULL DEFAULT 0,
                    `product_id` int(11) NOT NULL DEFAULT 0,
                    `category_id` int(11) NOT NULL DEFAULT 0,
                    `userid` int(11) NOT NULL DEFAULT 0,
                    `create_time` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_product` (`product_id`),
                    KEY `idx_category` (`category_id`),
                    KEY `idx_userid` (`userid`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            $this->ensureBindUseridColumn();
        }
    }

    /**相册列表
     * @param $params
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getAlbumLists($params, $uid=0)
    {
        $lists = WdXcxAlbumFolder::where('id', '>', 0)
            ->where(function ($query)use($params, $uid){
                if(!empty($params['key'])){
                    $query->whereLike('folder_name', '%'.$params['key'].'%');
                }
                if(!empty($params['album_id'])){
                    $query->where('pid', $params['album_id']);
                }else{
                    $query->where('pid', 0);
                }
                if($uid){
                    $query->where('uid', $uid);
                }else{
                    $query->where('uid', '>', 0);
                }
            })
            ->field('id,folder_name,folder_type,folder_desc,create_time,new_thumb,check_status,pid,uid,folder_desc,visible_type,sort,pic_layout')
            ->order('sort desc, id desc')
            ->paginate([
                'list_rows' => 10,
                'query' => input(),
            ])->each(function ($item){
                $item->son_count = $item->SonCount;
            });
        return $lists;
    }

    public function albumPicLists($params, $uid=0)
    {
        $lists = WdXcxUserAlbumPic::where('id', '>', 0)
            ->where(function ($query)use($params, $uid){
                if($uid){
                    $query->where('user_id', $uid);
                }else{
                    $query->where('user_id', '>', 0);
                }
                if(!empty($params['album_id'])){
                    $query->where('folder_id', $params['album_id']);
                }
            })->order('sort desc, id desc')
            ->paginate([
                'list_rows' => 10,
                'query' => input(),
            ]);
        return $lists;
    }

    /**保存用户相册图片
     * @param $album_info
     * @param $pids
     * @param $uid
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveAlbumPic($album_info, $pids, $uid)
    {
        $data = [];
        $createdRelations = [];
        $lastPicPath = ''; // 存储最后一张图片的路径
        foreach ($pids as $item){
            $pic = WdXcxPic::where('id', $item)->find();
            if($pic){
                $data[] = [
                    'sort' => 0,
                    'uniacid' => 1,
                    'user_id' => $uid,
                    'pic_id' => $item,
                    'folder_id' => $album_info->id,
                    'pic_name' => $pic->pic_name,
                    'set_top_time' => time(),
                    'upload_date' => date('Y-m-d'),
                ];
                // 记录最后一张图片的路径
                $lastPicPath = $pic->imgurl ?? ''; // 假设图片路径字段为 pic_url
            }
        }
        if(count($data) > 0){
            $syncStartTime = time() - 5;
            WdXcxUserAlbumPic::insertAll($data);
            $createdRelations = WdXcxUserAlbumPic::where('folder_id', $album_info->id)
                ->whereIn('pic_id', array_column($data, 'pic_id'))
                ->where('create_time', '>=', $syncStartTime)
                ->with(['picture'])
                ->order('id desc')
                ->select();
            // 如果有图片路径，则更新当前文件夹及所有上级文件夹的缩略图
            if($lastPicPath){
                $this->updateParentThumbs($album_info->id, $lastPicPath);
            }
        }
        return $createdRelations;

    }

    public function getParentLastPic($fid)
    {
        $need_pic = '';
        $pic = WdXcxUserAlbumPic::where('folder_id', $fid)
            ->order('id desc')
            ->page(2, 1)
            ->select();
        if($pic && isset($pic[0])){
            $need_pic = $pic[0];
            if($need_pic && $need_pic->picture && $need_pic->picture->imgurl){
                $need_pic = $need_pic->picture->imgurl;
            }
        }
        return $need_pic;
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



    /**获取评价列表页面顶部分类
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function getEvaluateCateLists($need_count=0)
    {
        $purpose = $this->getEvaluateCateData(11);
        if(!$need_count){
            return $purpose;
        }
        array_unshift($purpose,  '全部');
        $result = [];
        foreach ($purpose as $k => $item){
            $temp = [
                'name' => $item,
            ];
            if($k == 0){
                $temp['count'] = WdXcxEvaluateRecords::where('id', '>', 0)->count();
            }else{
                $temp['count'] = WdXcxEvaluateRecords::where('purpose', $item)->count();
            }
            $result[] = $temp;
        }
        return $result;
    }

    /**获取评价分类
     * @param $count
     * @return array
     */
    private function getEvaluateCateData($count=8)
    {
        $purpose = ['活动相册', '班级相册', '家庭相册', '商品相册', '商家相册', '研学活动', '教育培训', '宣传相册', '公司相册', '婚礼相册', '摄像相册'];
        $true_purpose = WdXcxEvaluateRecords::where('id', '>', 0)
            ->field('purpose, COUNT(purpose) as purpose_count')
            ->group('purpose')
            ->order('purpose_count desc, id desc')
            ->select()->toArray();
        $true_purpose = array_column($true_purpose, 'purpose');
        if(count($true_purpose) > $count){
            return $true_purpose;
        }else{
            $true_purpose = array_unique(array_merge($true_purpose, $purpose));
            return array_slice($true_purpose, 0, $count);
        }
    }

    /**用户提交评价信息
     * @param $param
     * @param $uid
     * @return void
     */
    public function userSubmitEvaluateInfo($param, $uid)
    {
        $openid = WdXcxUser::where('id', $uid)->value('openid');
        $this->checkContentWx($param['evaluate_content'], $openid);
        $record = new WdXcxEvaluateRecords();
        $record->data([
            'uid' => $uid,
            'purpose' => $param['purpose'],
            'evaluate_content' => $param['evaluate_content'],
        ]);
        $record->save();
    }

    public function getUserSubmitEvaluate($param)
    {
        $lists = WdXcxEvaluateRecords::where('id', '>', 0)
            ->where(function ($query)use($param){
                if(!empty($param['purpose']) && $param['purpose'] != '全部'){
                    $query->where('purpose', $param['purpose']);
                }
            })
            ->field('id,purpose,evaluate_content,create_time,uid,avatar,nickname')
            ->order('id desc')
            ->paginate(10)->each(function ($item){
                $user_info = $item->UserInfo;
                unset($user_info['mobile']);
                $item->user_info_data = $user_info;
            });
        return $lists;
    }



    /**用户创建分类/产品
     * @param $param
     * @param $uid
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createAlbumFolder($param, $uid)
    {
        $this->ensureProductStatusColumns();
        if(empty($param['folder_name'])){
            throwError('请输入名称');
        }
        if(empty($param['folder_type'])){
            throwError('请选择创建类型');
        }
        if($param['folder_type'] == 2){
            if (empty($param['pic_ids'])) {
                throwError('请选择花色图');
            }
        }
        $fid_param = $param['fid'];
        $first_fid = 0;
        $all_fids = [];

        if (!empty($fid_param)) {
            if (is_array($fid_param)) {
                $all_fids = $fid_param;
            } elseif (is_string($fid_param)) {
                 if (strpos($fid_param, ',') !== false) {
                    $all_fids = explode(',', $fid_param);
                 } else {
                    $all_fids = [$fid_param];
                 }
            } elseif (is_numeric($fid_param)) {
                 $all_fids = [$fid_param];
            }
        }
        
        // 过滤有效ID
        $valid_fids = [];
        foreach($all_fids as $v){
            $v = (int)$v;
            if($v > 0){
                $valid_fids[] = $v;
            }
        }
        $valid_fids = array_unique($valid_fids);
        
        // 取第一个作为主分类ID（用于校验和pid）
        if(!empty($valid_fids)){
            $first_fid = $valid_fids[0];
        }

        if($first_fid > 0){
            $folder = WdXcxAlbumFolder::where('id', $first_fid)->find();
            if(!$folder){
                throwError('指定分类不存在');
            }
            if($folder->folder_type == 2){
                throwError('请选择正确的分类');
            }
            if($folder->uid != $uid){
                if(!$folder->checkFolderRule($uid)){
                    throwError('您没有权限操作此分类');
                }
                if(!$folder->editer_create){
                    throwError('您没有权限操作此分类');
                }
            }
        }
        $folder = new WdXcxAlbumFolder();
        $saveData = [
            'folder_name' => $param['folder_name'],
            'folder_type' => $param['folder_type'],
            'new_thumb' => $param['new_thumb'],
            // 如果是分类，pid=$first_fid；如果是产品，pid=0（关系由关联表维护）
            'pid' => ($param['folder_type'] == 1) ? $first_fid : 0,
            'uid' => $uid,
            'new_thumb_time' => time(),
        ];
        if(isset($param['folder_desc'])){
            $saveData['folder_desc'] = $param['folder_desc'];
        }
        if(isset($param['private_type'])){
            $saveData['private_type'] = $param['private_type'];
        }
        if(isset($param['layout_type']) && $param['layout_type'] !== ''){
            $saveData['layout_type'] = (int)$param['layout_type'] === 2 ? 2 : 1;
        }
        if(isset($param['visible_type'])){
            $saveData['visible_type'] = $param['visible_type'];
        }
        if(isset($param['pic_ids'])){
            $saveData['pic_ids'] = is_array($param['pic_ids']) ? implode(',', $param['pic_ids']) : $param['pic_ids'];
        }
        if(isset($param['detail_pic_ids'])){
            $saveData['detail_pic_ids'] = is_array($param['detail_pic_ids']) ? implode(',', $param['detail_pic_ids']) : $param['detail_pic_ids'];
        }
        if(isset($param['hide_detail_pictures'])){
            $saveData['hide_detail_pictures'] = (int)$param['hide_detail_pictures'] === 1 ? 1 : 0;
        }
        $folder->save($saveData);

        // 如果是创建产品(type=2)且指定了分类(valid_fids不为空)，添加关联记录
        if ($param['folder_type'] == 2 && !empty($valid_fids)) {
             $this->ensureBindUseridColumn();
             foreach ($valid_fids as $cid) {
                 // 简单的权限/存在性检查
                 $cate = WdXcxAlbumFolder::where('id', $cid)->find();
                 if ($cate && $cate->folder_type == 1) {
                      // 权限检查
                      $hasAuth = ($cate->uid == $uid);
                      if (!$hasAuth) {
                          if (method_exists($cate, 'checkFolderRule') && $cate->checkFolderRule($uid) && $cate->editer_create) {
                              $hasAuth = true;
                          }
                      }
                      
                      if ($hasAuth) {
                         WdXcxProductCategoryBind::create([
                             'uniacid' => 0,
                             'product_id' => $folder->id,
                             'category_id' => $cid,
                             'userid' => $uid,
                             'create_time' => time()
                         ]);
                      }
                 }
             }
        }

        // 如果是产品，且上传了图片，自动将图片添加到产品内容中(wd_xcx_user_album_pic)
        // 注意：这里有两个概念，pic_ids字段只是作为"花色图"记录在产品属性里
        // 但通常产品里的图片也需要展示在列表里，所以这里根据需求，我们也可以把这些图片作为产品内容插入
        // 根据用户UI逻辑，"上传花色图"和"上传详情图"属于产品属性，而"批量上传照片"属于产品内容
        // 这里暂时只保存字段。如果用户需要在产品内容里看到，需要额外调用addAlbumPics
        $created = WdXcxAlbumFolder::where('id', $folder->id)->find();
        if ($created && (int)$created->folder_type === 2) {
            (new AiResourceBridgeService($this->app))->safeSyncProductPictures($uid, $created);
        }

        return $created;
    }

    public function updateAlbumFolder($param, $uid)
    {
        $folder = WdXcxAlbumFolder::where('id', $param['fid'])->find();
        if(!$folder){
            throwError('相册不存在');
        }
        if($folder->uid != $uid){
            throwError('您没有权限操作此相册');
        }
        $oldCoverIds = $folder->pic_ids ? $this->normalizeIdList($folder->pic_ids) : [];
        $oldDetailIds = $folder->detail_pic_ids ? $this->normalizeIdList($folder->detail_pic_ids) : [];
        $updateData = [];
        if(!empty($param['folder_name'])){
            $updateData['folder_name'] = $param['folder_name'];
        }
        if(isset($param['folder_desc'])){
            $updateData['folder_desc'] = $param['folder_desc'];
        }
        if(isset($param['private_type'])){
            // private_type 1:公开 2:私密 4:仅分享可见
            $updateData['private_type'] = $param['private_type'];
        }
        if(!empty($updateData)){
            $folder->save($updateData);
        }
        if ((int)$folder->folder_type === 2 && (isset($param['pic_ids']) || isset($param['detail_pic_ids']))) {
            $folder = WdXcxAlbumFolder::where('id', $folder->id)->find();
            $this->syncRemovedProductPictureLinks($uid, $folder, $oldCoverIds, $oldDetailIds, $param);
            (new AiResourceBridgeService($this->app))->safeSyncProductPictures($uid, $folder);
        }
    }

    public function editAlbumFolder($param, $uid)
    {
        $this->ensureProductStatusColumns();
        $folder = WdXcxAlbumFolder::where('id', $param['fid'])->find();
        if(!$folder){
            throwError('相册不存在');
        }
        if($folder->uid != $uid){
            throwError('您没有权限操作此相册');
        }
        $oldCoverIds = $folder->pic_ids ? $this->normalizeIdList($folder->pic_ids) : [];
        $oldDetailIds = $folder->detail_pic_ids ? $this->normalizeIdList($folder->detail_pic_ids) : [];
        // if ($folder->folder_type == 2 && isset($param['pic_ids']) && empty($param['pic_ids']) && ) {
        //     throwError('请选择花色图');
        // }
        $updateData = [];
        if(!empty($param['folder_name'])){
            $updateData['folder_name'] = $param['folder_name'];
        }
        if(isset($param['folder_desc'])){
            $updateData['folder_desc'] = $param['folder_desc'];
        }
        if(isset($param['private_type'])){
            $updateData['private_type'] = $param['private_type'];
        }
        if(isset($param['layout_type']) && $param['layout_type'] !== ''){
            $updateData['layout_type'] = (int)$param['layout_type'] === 2 ? 2 : 1;
        }
        if(isset($param['pic_layout']) && $param['pic_layout'] !== ''){
            $updateData['pic_layout'] = (int)$param['pic_layout'] === 1 ? 1 : 2;
        }
        if(isset($param['new_thumb']) && $param['new_thumb'] !== ''){
            $updateData['new_thumb'] = $param['new_thumb'];
            $updateData['new_thumb_time'] = time();
        }
        if(isset($param['visible_type'])){
            $updateData['visible_type'] = $param['visible_type'];
        }
        if(isset($param['pic_ids'])){
            $updateData['pic_ids'] = is_array($param['pic_ids']) ? implode(',', $param['pic_ids']) : $param['pic_ids'];
        }
        if(isset($param['detail_pic_ids'])){
            $updateData['detail_pic_ids'] = is_array($param['detail_pic_ids']) ? implode(',', $param['detail_pic_ids']) : $param['detail_pic_ids'];
        }
        if(isset($param['hide_detail_pictures'])){
            $updateData['hide_detail_pictures'] = (int)$param['hide_detail_pictures'] === 1 ? 1 : 0;
        }
        
        // 如果是产品(type=2)且提供了category_ids，更新分类绑定
        if ($folder->folder_type == 2 && isset($param['category_ids'])) {
             $this->setProductCategories($folder->id, $param['category_ids'], $uid);
             
             // 同时更新pid为第一个分类ID
             $cids = $param['category_ids'];
             if (!is_array($cids)) {
                 if (strpos($cids, ',') !== false) {
                    $cids = explode(',', $cids);
                 } else {
                    $cids = [$cids];
                 }
             }
             $valid_cids = [];
             foreach($cids as $c){
                 if((int)$c > 0) $valid_cids[] = (int)$c;
             }
             if(!empty($valid_cids)){
                 $updateData['pid'] = $valid_cids[0];
             }
        }

        if(!empty($updateData)){
            $folder->save($updateData);
        }
        if ((int)$folder->folder_type === 2 && (isset($param['pic_ids']) || isset($param['detail_pic_ids']))) {
            $folder = WdXcxAlbumFolder::where('id', $folder->id)->find();
            $this->syncRemovedProductPictureLinks($uid, $folder, $oldCoverIds, $oldDetailIds, $param);
            (new AiResourceBridgeService($this->app))->safeSyncProductPictures($uid, $folder);
        }
    }
    public function addAlbumPics($param, $uid)
    {
        $folder = WdXcxAlbumFolder::where('id', $param['fid'])->find();
        if(!$folder){
            throwError('相册不存在');
        }
        if($folder->uid != $uid){
            if(!$folder->checkFolderRule($uid)){
                 throwError('您没有权限操作此相册');
            }
        }
        
        $createdRelations = $this->saveAlbumPic($folder, $param['pic_ids'], $uid);
        if (!empty($createdRelations)) {
            $bridge = new AiResourceBridgeService($this->app);
            foreach ($createdRelations as $relation) {
                $bridge->safeSyncAlbumRelation($folder->uid, $relation, 'album');
            }
        }
    }

    public function setAlbumSort($param, $uid)
    {
        $sortData = $param['sort_data'];
        if(empty($sortData)) return;
        foreach($sortData as $item){
            if(isset($item['id']) && isset($item['sort'])){
                WdXcxAlbumFolder::where('id', $item['id'])
                    ->where('uid', $uid)
                    ->update(['sort' => $item['sort']]);
            }
        }
    }

    public function setAlbumPicSort($param, $uid)
    {
        $sortData = $param['sort_data'];
        if(empty($sortData)) return;
        $fid = $param['fid'];
        $folder = WdXcxAlbumFolder::where('id', $fid)->find();
        if(!$folder || $folder->uid != $uid){
             throwError('无权操作');
        }

        foreach($sortData as $item){
             if(isset($item['id']) && isset($item['sort'])){
                 WdXcxUserAlbumPic::where('id', $item['id'])
                     ->where('folder_id', $fid)
                     ->update(['sort' => $item['sort']]);
             }
        }
    }

    public function deleteAlbumFolder($param, $uid)
    {
        $fids = explode(',', $param['fid']);
        $del_type = $param['del_type'];
        foreach ($fids as $fid){
            $folder = WdXcxAlbumFolder::where('id', $fid)->find();
            if(!$folder){
                throwError('指定文件夹不存在');
            }
            if($folder->uid != $uid){
                if(!$folder->checkFolderRule($uid)){
                    throwError('您没有权限操作此文件夹');
                }
                if(!$folder->editer_delete){
                    throwError('您没有权限操作此文件夹');
                }
            }
            // 开启事务
            $this->pendingBridgeDeletes = [];
            Db::startTrans();
            try {
                $this->collectProductFieldPictureDeletes($folder, $uid, false);
                // 递归删除所有子文件夹
                $this->deleteChildFolders($fid, $uid, $del_type);
                // 删除当前文件夹中的所有图片记录
                $this->deleteFolderPictures($fid, $del_type, $uid);
                //删除文件夹的绑定
                $has_bind = WdXcxAlbumShareBind::where('fid', $folder->id)->find();
                if($has_bind){
                    $has_bind->force()->delete();
                }
                // 删除产品-分类关联表数据
                WdXcxProductCategoryBind::where('category_id', $folder->id)->delete();
                WdXcxProductCategoryBind::where('product_id', $folder->id)->delete();

                // 删除文件夹本身
                $folder->delete();
                // 提交事务
                Db::commit();
                $this->flushPendingBridgeDeletes($uid);
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->pendingBridgeDeletes = [];
                throwError($e->getMessage());
            }

        }
    }

    /**
     * 删除分类（如果有产品则不可删除）
     * @param $categoryId
     * @param $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteCategory($categoryId, $uid)
    {
        $folder = WdXcxAlbumFolder::where('id', $categoryId)->find();
        if(!$folder){
            throwError('分类不存在');
        }
        // 验证是否为分类
        if($folder->folder_type != 1){
            throwError('请选择正确的分类');
        }

        if($folder->uid != $uid){
            if(!$folder->checkFolderRule($uid)){
                throwError('您没有权限操作此分类');
            }
            if(!$folder->editer_delete){
                throwError('您没有权限操作此分类');
            }
        }

        // 检查是否有产品
        $hasProducts = WdXcxProductCategoryBind::where('category_id', $categoryId)->count();
        if($hasProducts > 0){
            throwError('该分类下存在产品，不可删除');
        }

        // 检查是否有子分类
        $hasChild = WdXcxAlbumFolder::where('pid', $categoryId)->count();
        if($hasChild > 0){
            throwError('该分类下存在子分类，不可删除');
        }

        // 开启事务
        Db::startTrans();
        try {
            // 删除文件夹的绑定
            $has_bind = WdXcxAlbumShareBind::where('fid', $folder->id)->find();
            if($has_bind){
                $has_bind->force()->delete();
            }
            
            // 删除分类本身
            $folder->delete();
            
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throwError($e->getMessage());
        }
    }

    /**
     * 递归删除所有子文件夹
     * @param $parentId 父文件夹ID
     * @param $uid 用户ID
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function deleteChildFolders($parentId, $uid, $del_type)
    {
        // 查找所有直接子文件夹
        $childFolders = WdXcxAlbumFolder::where('pid', $parentId)->select();
        foreach ($childFolders as $childFolder) {
            // 递归删除子文件夹的子文件夹
            $this->deleteChildFolders($childFolder->id, $uid, $del_type);
            $this->collectProductFieldPictureDeletes($childFolder, $uid, false);
            // 删除子文件夹中的所有图片记录
            $this->deleteFolderPictures($childFolder->id, $del_type, $uid);
            //删除文件夹的绑定
            $has_bind = WdXcxAlbumShareBind::where('fid', $childFolder->id)->find();
            if($has_bind){
                $has_bind->force()->delete();
            }
            // 删除产品-分类关联表数据
            WdXcxProductCategoryBind::where('category_id', $childFolder->id)->delete();
            WdXcxProductCategoryBind::where('product_id', $childFolder->id)->delete();

            // 删除子文件夹本身
            $childFolder->delete();
        }
    }

    /**
     * 删除文件夹中的所有图片记录
     * @param $folderId 文件夹ID
     * @return void
     */
    private function deleteFolderPictures($folderId, $del_type, $uid = 0)
    {
        // 查找文件夹中的所有图片记录
        $pictures = WdXcxUserAlbumPic::where('folder_id', $folderId)->select();
        foreach ($pictures as $picture) {
            // 这里可以根据业务需求决定是否同时删除WdXcxPic中的实际图片记录
            $deleteResource = false;
            if($del_type == 2){
                $pic_dat = WdXcxPic::where('id', $picture->pic_id)->find();
                if($pic_dat){
                    $pic_dat->delete();
                    $deleteResource = true;
                }
            }
            if ($uid) {
                $this->pendingBridgeDeletes[] = [
                    'pic_id' => (int)$picture->pic_id,
                    'b_folder_id' => (int)$folderId,
                    'b_relation_id' => (int)$picture->id,
                    'external_product_id' => (string)$folderId,
                    'role' => 'album',
                    'delete_resource' => $deleteResource,
                ];
            }
            // 删除图片记录
            $picture->delete();
        }
    }

    private function flushPendingBridgeDeletes($uid)
    {
        if (empty($this->pendingBridgeDeletes)) {
            return;
        }
        $bridge = new AiResourceBridgeService($this->app);
        foreach ($this->pendingBridgeDeletes as $item) {
            $picId = $item['pic_id'];
            unset($item['pic_id']);
            $bridge->safeMarkPictureDeleted($uid, $picId, $item);
        }
        $this->pendingBridgeDeletes = [];
    }

    private function normalizeIdList($raw)
    {
        $items = is_array($raw) ? $raw : explode(',', (string)$raw);
        $ids = [];
        foreach ($items as $item) {
            $id = (int)$item;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function getProductPictureEchoList($picIds)
    {
        $ids = $this->normalizeIdList($picIds);
        if (empty($ids)) {
            return [];
        }
        $order = implode(',', $ids);
        return WdXcxPic::whereIn('id', $ids)
            ->field('id, imgurl, pic_name, uniacid, file_type')
            ->orderRaw('FIELD(id, ' . $order . ')')
            ->select()
            ->each(function($pic){
                $pic->imgurl = $pic->TruePic;
                $pic->picture_url = $pic->TruePic;
                $pic->picture_url_original = removePicStyle($pic->TruePic);
            });
    }

    private function hydrateProductThumb($item)
    {
        if (!$item || (string)$item->new_thumb !== '') {
            return;
        }
        $ids = array_merge(
            $this->normalizeIdList($item->pic_ids ?? ''),
            $this->normalizeIdList($item->detail_pic_ids ?? '')
        );
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return;
        }
        $pic = WdXcxPic::whereIn('id', $ids)
            ->field('id,imgurl,uniacid,file_type')
            ->orderRaw('FIELD(id, ' . implode(',', $ids) . ')')
            ->find();
        if ($pic) {
            $item->new_thumb = $pic->TruePic;
        }
    }

    private function virtualProductRelationId($productId, $picId, $role)
    {
        $hash = sprintf('%u', crc32($productId . ':' . $picId . ':' . $role));
        return -1 * (int)substr($hash, 0, 9);
    }

    private function syncRemovedProductPictureLinks($uid, $folder, $oldCoverIds, $oldDetailIds, $param)
    {
        if (!$folder) {
            return;
        }
        $bridge = new AiResourceBridgeService($this->app);
        if (isset($param['pic_ids'])) {
            $newCoverIds = $this->normalizeIdList($param['pic_ids']);
            foreach (array_diff($oldCoverIds, $newCoverIds) as $removedPicId) {
                $bridge->safeMarkPictureDeleted($uid, $removedPicId, [
                    'b_folder_id' => (int)$folder->id,
                    'b_relation_id' => $this->virtualProductRelationId((int)$folder->id, (int)$removedPicId, 'cover'),
                    'external_product_id' => (string)$folder->id,
                    'role' => 'cover',
                    'delete_resource' => false,
                ]);
            }
        }
        if (isset($param['detail_pic_ids'])) {
            $newDetailIds = $this->normalizeIdList($param['detail_pic_ids']);
            foreach (array_diff($oldDetailIds, $newDetailIds) as $removedPicId) {
                $bridge->safeMarkPictureDeleted($uid, $removedPicId, [
                    'b_folder_id' => (int)$folder->id,
                    'b_relation_id' => $this->virtualProductRelationId((int)$folder->id, (int)$removedPicId, 'detail'),
                    'external_product_id' => (string)$folder->id,
                    'role' => 'detail',
                    'delete_resource' => false,
                ]);
            }
        }
    }

    private function collectProductFieldPictureDeletes($folder, $uid, $deleteResource = false)
    {
        if (!$folder || (int)$folder->folder_type !== 2 || !$uid) {
            return;
        }
        foreach ($this->normalizeIdList($folder->pic_ids ?? '') as $picId) {
            $this->pendingBridgeDeletes[] = [
                'pic_id' => (int)$picId,
                'b_folder_id' => (int)$folder->id,
                'b_relation_id' => $this->virtualProductRelationId((int)$folder->id, (int)$picId, 'cover'),
                'external_product_id' => (string)$folder->id,
                'role' => 'cover',
                'delete_resource' => $deleteResource,
            ];
        }
        foreach ($this->normalizeIdList($folder->detail_pic_ids ?? '') as $picId) {
            $this->pendingBridgeDeletes[] = [
                'pic_id' => (int)$picId,
                'b_folder_id' => (int)$folder->id,
                'b_relation_id' => $this->virtualProductRelationId((int)$folder->id, (int)$picId, 'detail'),
                'external_product_id' => (string)$folder->id,
                'role' => 'detail',
                'delete_resource' => $deleteResource,
            ];
        }
    }


    /**获取产品、分类列表
     * @param $param
     * @param $uid
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getAlbumFolderLists($param, $uid)
    {
        $this->ensureProductStatusColumns();
        $fid = $param['fid'];
        $order = 'set_top desc, sort asc, set_top_time desc';
        $folder_info = '';
        $share_str = '';
        $need_pwd = 0;
        $user_pwd = '';
        $option_flag = true;
        
        // Target UID logic
        $target_uid = isset($param['target_uid']) ? $param['target_uid'] : 0;
        if (!$target_uid && !empty($param['target_user_id'])) {
            $target_uid = $param['target_user_id'];
        }
        $visitor_uid = $uid;
        $owner_uid = ($target_uid && $target_uid != $visitor_uid) ? $target_uid : $visitor_uid;
        $is_visiting_others = ($owner_uid != $visitor_uid);

        if($fid){
            $folder_info = WdXcxAlbumFolder::where('id', $fid)->find();
            if(!$folder_info){
                throwError('指定分类不存在');
            }
            if($folder_info->private_type == 2 && $folder_info->uid != $visitor_uid){
                throwError('此内容为私有，请勿访问');
            }
            $order .= ', id desc';
        
            //生成分享码
            $redis = new Redis(GetRedisConf());
            $share_str = $redis->get('share_album_'.$fid);
            if(!$share_str){
                $share_str = $fid.$this->generateRandomString(10);
                $redis->set('share_album_'.$fid, $share_str);
                $redis->set($share_str, $fid);
            }
            if(!empty($param['link_share_str'])){
                $link_share_str = $redis->get($param['link_share_str']);
                if(!$link_share_str){
                    $need_pwd = 1;
                }
            }
            $user_pwd = WdXcxUser::where('id', $folder_info->uid)->value('upload_pwd');
            $folder_info->upload_field = $folder_info->upload_field ? $folder_info->upload_field : [];

            // Enhance folder_info with images for Edit Echo
            $folder_info->pic_ids_arr = [];
            $folder_info->detail_pic_ids_arr = [];
            
            if($folder_info->pic_ids){
                $folder_info->pic_ids_arr = $this->getProductPictureEchoList($folder_info->pic_ids);
            }
            if($folder_info->detail_pic_ids){
                $folder_info->detail_pic_ids_arr = $this->getProductPictureEchoList($folder_info->detail_pic_ids);
            }

        }
        if(!empty($param['share_uid']) && $param['fid'] && $param['share_uid'] != $visitor_uid){ // 分享绑定
            $this->bindShreAlbum($param['share_uid'], $visitor_uid, $fid);
        }
        $share_folders = [];
        if(!$fid && !$is_visiting_others){
            //查询绑定的产品
            $share_folders = WdXcxAlbumShareBind::where('bind_uid', $visitor_uid)->column('fid');
        }
        if($fid){
            if($folder_info->uid != $visitor_uid){
                if(!$folder_info->checkFolderRule($visitor_uid)){
                    $option_flag = false;
                }
                if($folder_info->private_type == 3){
                    //查询有没输入密码的记录
                    $has = WdXcxVisitFolderPwd::where([
                        'uid' => $visitor_uid,
                        'folder' => $folder_info->id,
                    ])->find();
                    $need_pwd = $has ? 0 : 1;
                }
            }
        }
        $this->ensureBindUseridColumn();
        $this->ensureBindUseridColumn();
        if ((int)($param['folder_type'] ?? 0) === 1) {
            return $this->getCategoryFolderLists($param, $fid, $owner_uid, $visitor_uid, $folder_info, $share_str, $need_pwd, $user_pwd, $option_flag);
        }
        $lists = WdXcxAlbumFolder::where(function ($query)use($param, $fid, $share_folders, $owner_uid, $is_visiting_others){
                if(!empty($param['key'])){
                    $query->whereLike('folder_name', '%'.$param['key'].'%');
                }
                if($fid){
                    $bound_ids = WdXcxProductCategoryBind::where('category_id', $fid)->where('userid', $owner_uid)->column('product_id');
                    $query->where(function($subQ) use ($fid, $bound_ids){
                        $subQ->where('pid', $fid);
                        if(!empty($bound_ids)){
                            $subQ->whereOr('id', 'in', $bound_ids);
                        }
                    });
                }else{
                    $query->where(function($query)use($owner_uid){
                        $query->where('uid', $owner_uid);
                    });
                    
                    if(!$is_visiting_others){
                        $query->whereOr(function ($query)use($share_folders){
                            $query->where('id', 'in', $share_folders);
                        });
                    }
                }
                if($param['folder_type'] && $param['folder_type'] != 0){
                    $query->where('folder_type', $param['folder_type']);
                }
                
                if($is_visiting_others){
                    $query->where('private_type', '<>', 2);
                }
            })
            ->field('id,folder_name,folder_type,folder_desc,private_type,layout_type,pic_layout,new_thumb,sort,create_time,share_times, visit_times,uid, set_top, is_hot, sort, pic_ids, detail_pic_ids')
            ->order('is_hot desc, ' . $order)
            ->paginate($param['limit'] ?? 10)->each(function ($item)use($visitor_uid){
                $item->pic_ids_arr = $this->normalizeIdList($item->pic_ids);
                $item->detail_pic_ids_arr = $this->normalizeIdList($item->detail_pic_ids);
                if ((int)$item->folder_type === 2) {
                    $this->hydrateProductThumb($item);
                }
                $item->son_count = $item->SonCount;
                // 分类增加子产品数量（去重：直系产品 + 关联产品）
                if ($item->folder_type == 1) {
                    $bound_ids = \app\common\model\album\WdXcxProductCategoryBind::where('category_id', $item->id)->where('userid', $item->uid)->column('product_id');
                    $direct_ids = \app\common\model\album\WdXcxAlbumFolder::where('pid', $item->id)
                        ->where('folder_type', 2)
                        ->column('id');
                    $all_ids = array_unique(array_merge($bound_ids ?: [], $direct_ids ?: []));
                    if (!empty($all_ids)) {
                        $countQuery = \app\common\model\album\WdXcxAlbumFolder::whereIn('id', $all_ids)
                            ->where('folder_type', 2)
                            ->where('uid', $item->uid);
                        if ($item->uid != $visitor_uid) {
                            $countQuery->where('private_type', '<>', 2);
                        }
                        $item->product_count = $countQuery->count();
                    } else {
                        $item->product_count = 0;
                    }
                }
                if($item->uid != $visitor_uid){
                    $item->folder_name = '@'.$item->UserInfo['nickname'].$item->folder_name;
                }
                $item->level = $item->FolderLeval;
            });
        $total_num = $lists->total();
        $total_folder = WdXcxAlbumFolder::where('folder_type', 1)
        ->where(function ($query)use($param, $fid, $share_folders, $owner_uid, $is_visiting_others){
            if(!empty($param['key'])){
                $query->whereLike('folder_name', '%'.$param['key'].'%');
            }
            if($fid){
                $bound_ids = WdXcxProductCategoryBind::where('category_id', $fid)->where('userid', $owner_uid)->column('product_id');
                $query->where(function($subQ) use ($fid, $bound_ids){
                    $subQ->where('pid', $fid);
                    if(!empty($bound_ids)){
                        $subQ->whereOr('id', 'in', $bound_ids);
                    }
                });
            }else{
                $query->where(function($query)use($owner_uid){
                    $query->where('uid', $owner_uid);
                });
                
                if(!$is_visiting_others){
                    $query->whereOr(function ($query)use($share_folders){
                        $query->where('id', 'in', $share_folders);
                    });
                }
            }
            if($is_visiting_others){
                $query->where('private_type', '<>', 2);
            }
        })->count();
        $total_album = WdXcxAlbumFolder::where('folder_type', 2)
            ->where(function ($query)use($param, $fid, $share_folders, $owner_uid, $is_visiting_others){
                if(!empty($param['key'])){
                    $query->whereLike('folder_name', '%'.$param['key'].'%');
                }
                if($fid){
                    $bound_ids = WdXcxProductCategoryBind::where('category_id', $fid)->where('userid', $owner_uid)->column('product_id');
                    $query->where(function($subQ) use ($fid, $bound_ids){
                        $subQ->where('pid', $fid);
                        if(!empty($bound_ids)){
                            $subQ->whereOr('id', 'in', $bound_ids);
                        }
                    });
                }else{
                    $query->where(function($query)use($owner_uid){
                        $query->where('uid', $owner_uid);
                    });
                    
                    if(!$is_visiting_others){
                        $query->whereOr(function ($query)use($share_folders){
                            $query->where('id', 'in', $share_folders);
                        });
                    }
                }
                if($is_visiting_others){
                    $query->where('private_type', '<>', 2);
                }
            })->count();
        return [
            'lists' => $lists,
            'folder_info' => $folder_info,
            'share_str' => $share_str,
            'need_pwd' => $need_pwd,
            'user_pwd' => $user_pwd,
            'option_flag' => $option_flag,
            'total_num' => $total_num,
            'total_folder' => $total_folder,
            'total_album' => $total_album,
        ];
    }

    private function getCategoryFolderLists($param, $fid, $owner_uid, $visitor_uid, $folder_info, $share_str, $need_pwd, $user_pwd, $option_flag)
    {
        $queryPid = (int)$fid;
        $limit = $param['limit'] ?? 100;
        $lists = WdXcxAlbumFolder::where('folder_type', 1)
            ->where('uid', $owner_uid)
            ->where('pid', $queryPid)
            ->where(function ($query)use($param){
                if(!empty($param['key'])){
                    $query->whereLike('folder_name', '%'.$param['key'].'%');
                }
            })
            ->field('id,folder_name,folder_type,folder_desc,private_type,layout_type,pic_layout,new_thumb,sort,create_time,share_times,visit_times,uid,set_top,pid')
            ->order('sort desc, set_top desc, set_top_time desc, id desc')
            ->paginate([
                'list_rows' => $limit,
                'query' => input(),
            ])->each(function ($item)use($visitor_uid){
                $item->pic_ids_arr = [];
                $item->detail_pic_ids_arr = [];
                $item->son_count = $this->getCategoryChildCount($item->id);
                $item->child_count = $item->son_count;
                $item->product_count = 0;
                $item->children = $this->getCategoryChildren($item->id, $item->uid);
                if($item->uid != $visitor_uid){
                    $item->folder_name = '@'.$item->UserInfo['nickname'].$item->folder_name;
                }
                $item->level = $item->FolderLeval;
            });
        $total_num = $lists->total();
        return [
            'lists' => $lists,
            'folder_info' => $folder_info,
            'share_str' => $share_str,
            'need_pwd' => $need_pwd,
            'user_pwd' => $user_pwd,
            'option_flag' => $option_flag,
            'total_num' => $total_num,
            'total_folder' => $total_num,
            'total_album' => 0,
        ];
    }

    private function getCategoryChildCount($categoryId)
    {
        return WdXcxAlbumFolder::where('folder_type', 1)
            ->where('pid', $categoryId)
            ->count();
    }

    private function getCategoryChildren($categoryId, $uid)
    {
        return WdXcxAlbumFolder::where('folder_type', 1)
            ->where('uid', $uid)
            ->where('pid', $categoryId)
            ->field('id,folder_name,folder_type,folder_desc,private_type,layout_type,pic_layout,new_thumb,sort,create_time,share_times,visit_times,uid,set_top,pid')
            ->order('sort desc, set_top desc, set_top_time desc, id desc')
            ->select()
            ->each(function ($child){
                $child->pic_ids_arr = [];
                $child->detail_pic_ids_arr = [];
                $child->son_count = $this->getCategoryChildCount($child->id);
                $child->child_count = $child->son_count;
                $child->product_count = 0;
                $child->children = $this->getCategoryChildren($child->id, $child->uid);
                $child->level = $child->FolderLeval;
            });
    }

    public function getShowAlbumFolderLists($param, $uid)
    {
        $fid = $param['fid'];
        // Default sort: custom sort order (desc) then set_top (desc), then time (desc)
        // Added 'sort desc' to prioritize manually sorted items
        $order = 'set_top desc, sort desc, set_top_time desc';
        
        // Target UID logic
        $target_uid = isset($param['target_uid']) ? $param['target_uid'] : 0;
        $visitor_uid = $uid;
        $owner_uid = ($target_uid && $target_uid != $visitor_uid) ? $target_uid : $visitor_uid;
        $is_visiting_others = ($owner_uid != $visitor_uid);

        if($fid){
            $folder_info = WdXcxAlbumFolder::where('id', $fid)->find();
            if(!$folder_info){
                throwError('指定分类不存在');
            }
            if($folder_info->sort_type == 1){ // 按创建时间排序 新到旧
                $order .= ', id desc';
            }elseif ($folder_info->sort_type == 2){// 按创建时间排序 旧到新
                $order .= ', id asc';
            }else{
                $order .= ', id desc';
            }
        }
        $share_folders = [];
        if(!$fid && !$is_visiting_others){
            //查询绑定的产品
            $share_folders = WdXcxAlbumShareBind::where('bind_uid', $visitor_uid)->column('fid');
        }
        $lists = WdXcxAlbumFolder::where(function ($query)use($param, $fid, $share_folders, $owner_uid, $is_visiting_others){
            if($fid){
                // 在分类详情页，获取关联的产品（多对多）
                // 1. 获取所有关联的产品ID
                $product_ids = WdXcxProductCategoryBind::where('category_id', $fid)->where('userid', $owner_uid)->column('product_id');
                // 2. 查询这些产品
                if(!empty($product_ids)){
                     $query->whereIn('id', $product_ids)->where('uid', $owner_uid);
                }else{
                    // 如果没有关联产品，且是查询分类内容，则返回空（通过设置一个不可能的条件）
                    $query->where('id', -1);
                }
            }else{
                // 首页/根目录逻辑保持不变(显示顶级分类和未分类产品，或者所有顶级项)
                $query->where(function($query)use($owner_uid){
                    $query->where('uid', $owner_uid);
                });
                
                if(!$is_visiting_others){
                    $query->whereOr(function ($query)use($share_folders){
                        $query->where('id', 'in', $share_folders);
                    });
                }
            }
            
            if($is_visiting_others){
                $query->where('private_type', '<>', 2);
            }
        })->field('id,folder_name,folder_type,folder_desc,new_thumb,uid, sort')
            ->order($order)
            ->select();
        foreach ($lists as $item){
            if($item->uid != $visitor_uid){
                $item->folder_name = '@'.$item->UserInfo['nickname'].$item->folder_name;
            }
            $item->isChecked = false;
            $item->level = $item->FolderLeval;
        }
        if(!$fid && !$is_visiting_others){
            $collect = [
                'id' => -1,
                'folder_name' => '我的收藏',
                'folder_type' => 2,
                'isChecked' => false,
                'sort' => 0
            ];
            $my_collect = WdXcxUserCollectPics::where('uid', $visitor_uid)
                ->order('id desc')
                ->find();
            if($my_collect && $my_collect->picture){
                $collect['new_thumb'] = $my_collect->picture->TruePic;
            }else{
                $collect['new_thumb'] = '';
            }
            $lists = $lists->toArray();
            array_unshift($lists, $collect);
        }
        return $lists;
    }

    /**获取所有产品列表（用于选择添加）
     * @param $uid
     * @return mixed
     */
    public function getAllProducts($uid)
    {
        // 获取所有属于用户的产品
        $lists = WdXcxAlbumFolder::where('uid', $uid)
            ->where('folder_type', 2) // 仅产品
            ->field('id,folder_name,folder_desc,new_thumb,pid')
            ->order('create_time desc')
            ->select()
            ->each(function($item){
                $item->son_count = $item->SonCount; // 图片数量
                $item->isChecked = false;
            });
        return $lists;
    }

    /**批量添加/移除产品到分类（移动）
     * @param $cate_id
     * @param $product_ids
     * @param $uid
     * @param int $type 1:add 2:remove
     * @return void
     */
    public function addProductsToCategory($cate_id, $product_ids, $uid, $type = 1)
    {
        $this->ensureBindTable();
        Log::info('[AlbumService:addProductsToCategory] start uid='.$uid.' cate_id='.$cate_id.' type='.$type.' raw_product_ids='.json_encode($product_ids, JSON_UNESCAPED_UNICODE));
        // 检查分类是否存在
        $cate = WdXcxAlbumFolder::where('id', $cate_id)->where('uid', $uid)->where('folder_type', 1)->find();
        if(!$cate){
            Log::warning('[AlbumService:addProductsToCategory] category not found or not belong to user uid='.$uid.' cate_id='.$cate_id);
            throwError('分类不存在');
        }
        
        // 处理 product_ids 可能是字符串的情况 (e.g. "[136, 137]" or "136,137")
        if (!is_array($product_ids)) {
            // 尝试 JSON 解码
            $decoded = json_decode($product_ids, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $product_ids = $decoded;
            } else {
                // 强制清洗字符串：移除 [] " ' 空格，然后按逗号分割
                $clean_ids = str_replace(['[', ']', '"', "'", ' '], '', (string)$product_ids);
                if ($clean_ids === '') {
                    $product_ids = [];
                } else {
                    $product_ids = explode(',', $clean_ids);
                }
            }
        } else {
            // 规范化数组：支持数组元素为字符串 "[171,172]" 或 "171, 172"
            $normalized = [];
            foreach ($product_ids as $item) {
                if (is_array($item)) {
                    foreach ($item as $sub) {
                        $normalized[] = $sub;
                    }
                } else {
                    $str = (string)$item;
                    $decoded = json_decode($str, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        foreach ($decoded as $sub) {
                            $normalized[] = $sub;
                        }
                    } else {
                        $clean_ids = str_replace(['[', ']', '"', "'", ' '], '', $str);
                        if ($clean_ids !== '') {
                            foreach (explode(',', $clean_ids) as $sub) {
                                $normalized[] = $sub;
                            }
                        }
                    }
                }
            }
            $product_ids = $normalized;
        }
        Log::info('[AlbumService:addProductsToCategory] parsed_product_ids='.json_encode($product_ids, JSON_UNESCAPED_UNICODE));
        // 去重并转为整型
        $product_ids = array_values(array_unique(array_map(function($v){ return (int)$v; }, (array)$product_ids)));
        Log::info('[AlbumService:addProductsToCategory] normalized_product_ids='.json_encode($product_ids, JSON_UNESCAPED_UNICODE));

        $added_count = 0;
        $removed_count = 0;
        $diagnostics = [];
        
        // 获取当前分类下所有的产品ID
        $current_bind_ids = WdXcxProductCategoryBind::where([
            'category_id' => $cate_id,
            'userid' => $uid
        ])->column('product_id');

        // 计算需要添加和需要删除的ID
        $to_add = array_diff($product_ids, $current_bind_ids);
        $to_remove = array_diff($current_bind_ids, $product_ids);

        // 执行添加
        foreach ($to_add as $pid) {
            $pid = (int)$pid;
            if ($pid <= 0) continue;

            $product = WdXcxAlbumFolder::where('id', $pid)->where('folder_type', 2)->where('uid', $uid)->find();
            if(!$product){
                $diagnostics[] = ['pid' => $pid, 'reason' => '产品不存在或不属于当前用户'];
                continue;
            }

            try {
                WdXcxProductCategoryBind::create([
                    'uniacid' => 0,
                    'product_id' => $pid,
                    'category_id' => $cate_id,
                    'userid' => $uid,
                    'create_time' => time()
                ]);
                $added_count++;
                $diagnostics[] = ['pid' => $pid, 'reason' => '新增成功'];
            } catch (\Throwable $e) {
                $diagnostics[] = ['pid' => $pid, 'reason' => '新增失败:'.$e->getMessage()];
                Log::error('[AlbumService:addProductsToCategory] insert exception pid='.$pid.' cate_id='.$cate_id.' uid='.$uid.' err='.$e->getMessage());
            }
        }

        // 执行移除
        if (!empty($to_remove)) {
            $res = WdXcxProductCategoryBind::where([
                'category_id' => $cate_id,
                'userid' => $uid
            ])->whereIn('product_id', $to_remove)->delete();
            
            $removed_count = count($to_remove); // Assuming delete is successful for all found
            foreach($to_remove as $pid) {
                $diagnostics[] = ['pid' => $pid, 'reason' => '移除成功'];
            }
        }

        Log::info('[AlbumService:addProductsToCategory] sync complete added='.$added_count.' removed='.$removed_count.' uid='.$uid.' cate_id='.$cate_id);
        
        return [
            'added_count' => $added_count,
            'removed_count' => $removed_count,
            'cate_id' => $cate_id,
            'diagnostics' => $diagnostics
        ];

    }

    public function updateProductStatus($param, $uid)
    {
        $this->ensureProductStatusColumns();
        $product = WdXcxAlbumFolder::where('id', $param['id'])
            ->where('folder_type', 2)
            ->where('uid', $uid)
            ->find();
        if(!$product){
            throwError('产品不存在');
        }
        $updateData = [];
        if(array_key_exists('is_hot', $param) && $param['is_hot'] !== null){
            $updateData['is_hot'] = $param['is_hot'] ? 1 : 0;
        }
        if(array_key_exists('private_type', $param) && $param['private_type'] !== null){
            $updateData['private_type'] = (int)$param['private_type'];
        }
        if(array_key_exists('pic_layout', $param) && $param['pic_layout'] !== null){
            $updateData['pic_layout'] = (int)$param['pic_layout'] === 1 ? 1 : 2;
        }
        if(array_key_exists('new_thumb', $param) && $param['new_thumb'] !== null && $param['new_thumb'] !== ''){
            $updateData['new_thumb'] = $param['new_thumb'];
            $updateData['new_thumb_time'] = time();
        }
        if(array_key_exists('hide_detail_pictures', $param) && $param['hide_detail_pictures'] !== null){
            $updateData['hide_detail_pictures'] = (int)$param['hide_detail_pictures'] === 1 ? 1 : 0;
        }
        if(!empty($updateData)){
            $product->save($updateData);
        }
    }

    public function addProductToCategories($product_id, $category_ids, $uid)
    {
        $this->ensureBindTable();
        $product = WdXcxAlbumFolder::where('id', $product_id)->where('folder_type', 2)->find();
        if(!$product){
            throwError('产品不存在');
        }
        if($product->uid != $uid){
            throwError('无权限操作该产品');
        }
        if (empty($category_ids)) {
            throwError('请选择分类');
        }
        if (!is_array($category_ids)) {
            $decoded = json_decode($category_ids, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $category_ids = $decoded;
            } else {
                $clean_ids = str_replace(['[', ']', '"', "'", ' '], '', (string)$category_ids);
                if ($clean_ids === '') {
                    $category_ids = [];
                } else {
                    $category_ids = explode(',', $clean_ids);
                }
            }
        }
        $added_count = 0;
        foreach ($category_ids as $cid) {
            $cid = (int)$cid;
            if ($cid <= 0) continue;
            $cate = WdXcxAlbumFolder::where('id', $cid)->where('uid', $uid)->where('folder_type', 1)->find();
            if(!$cate){
                continue;
            }
            $exists = WdXcxProductCategoryBind::where([
                'product_id' => $product_id,
                'category_id' => $cid,
                'userid' => $uid
            ])->find();
            if (!$exists) {
                WdXcxProductCategoryBind::create([
                    'uniacid' => 0,
                    'product_id' => $product_id,
                    'category_id' => $cid,
                    'userid' => $uid,
                    'create_time' => time()
                ]);
                $added_count++;
            }
        }
        if (empty($category_ids) && $added_count === 0) {
            throwError('未检测到有效的分类ID，请检查输入格式');
        }
    }

    public function updateProductCategories($product_id, $add_ids, $remove_ids, $uid)
    {
        $this->ensureBindTable();
        $product = WdXcxAlbumFolder::where('id', $product_id)->where('folder_type', 2)->find();
        if(!$product){
            throwError('产品不存在');
        }
        if($product->uid != $uid){
            throwError('无权限操作该产品');
        }
        if (!is_array($add_ids)) {
            $decoded = json_decode((string)$add_ids, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $add_ids = $decoded;
            } else {
                $clean_ids = str_replace(['[', ']', '"', "'", ' '], '', (string)$add_ids);
                $add_ids = $clean_ids === '' ? [] : explode(',', $clean_ids);
            }
        }
        if (!is_array($remove_ids)) {
            $decoded = json_decode((string)$remove_ids, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $remove_ids = $decoded;
            } else {
                $clean_ids = str_replace(['[', ']', '"', "'", ' '], '', (string)$remove_ids);
                $remove_ids = $clean_ids === '' ? [] : explode(',', $clean_ids);
            }
        }
        if(!empty($add_ids)){
            $this->addProductToCategories($product_id, $add_ids, $uid);
        }
        if(!empty($remove_ids)){
            foreach ($remove_ids as $cid) {
                $cid = (int)$cid;
                if ($cid <= 0) continue;
                $cate = WdXcxAlbumFolder::where('id', $cid)->where('uid', $uid)->where('folder_type', 1)->find();
                if(!$cate){
                    continue;
                }
                WdXcxProductCategoryBind::where([
                    'product_id' => $product_id,
                    'category_id' => $cid,
                    'userid' => $uid
                ])->delete();
            }
        }
    }
    
    public function setProductCategories($product_id, $category_ids, $uid)
    {
        $this->ensureBindTable();
        $product = WdXcxAlbumFolder::where('id', $product_id)->where('folder_type', 2)->find();
        if(!$product){
            throwError('产品不存在');
        }
        if($product->uid != $uid){
            throwError('无权限操作该产品');
        }
        if (!is_array($category_ids)) {
            $decoded = json_decode((string)$category_ids, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $category_ids = $decoded;
            } else {
                $clean_ids = str_replace(['[', ']', '"', "'", ' '], '', (string)$category_ids);
                $category_ids = $clean_ids === '' ? [] : explode(',', $clean_ids);
            }
        }
        $desired = array_unique(array_map('intval', (array)$category_ids));
        $desired = array_values(array_filter($desired, function($v){ return $v > 0; }));
        $current = WdXcxProductCategoryBind::where('product_id', $product_id)->where('userid', $uid)->column('category_id');
        $current = array_map('intval', $current ?: []);
        $add = array_values(array_diff($desired, $current));
        $remove = array_values(array_diff($current, $desired));
        if (!empty($add) || !empty($remove)) {
            $this->updateProductCategories($product_id, $add, $remove, $uid);
        }
        return [
            'add_count' => count($add),
            'remove_count' => count($remove),
            'final_ids' => $desired,
        ];
    }

    public function setProductTop($product_id, $uid)
    {
        $this->ensureSetTopColumns();
        $product = WdXcxAlbumFolder::where('id', $product_id)->where('folder_type', 2)->find();
        if(!$product){
            throwError('产品不存在');
        }
        if($product->uid != $uid){
            throwError('无权限操作');
        }
        $product->set_top = 1;
        $product->set_top_time = time();
        $product->save();
        return true;
    }

    public function unsetProductTop($product_id, $uid)
    {
        $this->ensureSetTopColumns();
        $product = WdXcxAlbumFolder::where('id', $product_id)->where('folder_type', 2)->find();
        if(!$product){
            throwError('产品不存在');
        }
        if($product->uid != $uid){
            throwError('无权限操作');
        }
        $product->set_top = 0;
        $product->set_top_time = 0;
        $product->save();
        return true;
    }

    /**绑定产品分享
     * @param $share_uid
     * @param $uid
     * @param $fid
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function bindShreAlbum($share_uid, $uid, $fid)
    {
        //检查分享者是否有绑定数量
        $share_user = WdXcxUser::where('id', $share_uid)->find();
        if(!$share_user){
            throwError('分享者不存在');
        }
        $user_grade_info = $share_user->VipGradeInfo;
        if($user_grade_info['grade_level'] > 0 && (strtotime($user_grade_info['end_time'].' 23:59:59') > time() || $user_grade_info['end_time'] == 0)){
            $editor_number = WdXcxVipgrade::where('grade_level', $user_grade_info['grade_level'])->value('editor_number');
            $has_bind_number = WdXcxAlbumShareBind::where('share_uid', $share_uid)->count();
            if($has_bind_number >= $editor_number){
                return;
            }
            $has_bind = WdXcxAlbumShareBind::where([
                'share_uid' => $share_uid,
                'bind_uid' => $uid,
                'fid' => $fid,
            ])->find();
            if(!$has_bind){
                WdXcxAlbumShareBind::create([
                    'share_uid' => $share_uid,
                    'bind_uid' => $uid,
                    'fid' => $fid,
                    'create_time' => time(),
                ]);
            }
        }

    }

    /**获取相册图片列表
     * @param $param
     * @param $uid
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getAlbumPicLists($param, $uid)
    {
        $fid = $param['fid'];
        if(empty($fid)){
            throwError('请选择相册');
        }
        $folder = WdXcxAlbumFolder::where('id', $fid)->where('folder_type', 2)->find();
        if(!$folder){
            throwError('指定相册不存在');
        }
        $option_flag = true;
        if($folder->uid != $uid){
            if(!empty($param['share_uid']) && $param['fid'] && $param['share_uid'] != $uid){ // 分享绑定
                $this->bindShreAlbum($param['share_uid'], $uid, $fid);
            }
            if(!$folder->checkFolderRule($uid)){
                $option_flag = false;
            }
            if($folder->private_type == 2){
                throwError('此相册为私有相册，请勿访问');
            }
        }
        $share_str = '';
        $need_pwd = 0;
        $user_pwd = '';
        //生成分享码
        $redis = new Redis(GetRedisConf());
        $share_str = $redis->get('share_album_'.$fid);
        if(!$share_str){
            $share_str = $fid.$this->generateRandomString(10);
            $redis->set('share_album_'.$fid, $share_str);
            $redis->set($share_str, $fid);
        }
        if(!empty($param['link_share_str'])){
            $link_share_str = $redis->get($param['link_share_str']);
            if(!$link_share_str){
                $need_pwd = 1;
            }
        }
        $user_pwd = WdXcxUser::where('id', $folder->uid)->value('upload_pwd');
        if($folder->uid != $uid && $folder->private_type == 3){
            //查询有没输入密码的记录
            $has = WdXcxVisitFolderPwd::where([
                'uid' => $uid,
                'folder' => $folder->id,
            ])->find();
            $need_pwd = $has ? 0 : 1;
        }
        $lists = [];
        if($folder->show_upload_date){
            //查询置顶的
            $set_top = WdXcxUserAlbumPic::where('folder_id', $fid)
                ->where(function ($quey)use($param){
                    if(!empty($param['key'])){
                        $quey->where('pic_beizhu', 'like', '%'.$param['key'].'%');
                    }
                })
                ->where('set_top', 1)
                ->order('set_top_time desc')
                ->field('id, pic_id, set_top, pic_beizhu, user_id, create_time')
                ->select()->toArray();
            $set_top_ids = [];
            if(count($set_top) > 0){
                $set_top_ids = array_column($set_top, 'id');
                foreach ($set_top as $sk => $set_item){
                    // 获取图片信息
                    $picture = WdXcxPic::where('id', $set_item['pic_id'])->find();
                    if ($picture) {
                        $set_top[$sk]['picture_url'] = $picture->TruePic;
                        $set_top[$sk]['picture_url_original'] = removePicStyle($picture->TruePic);
                        $set_top[$sk]['file_type'] = $picture->file_type;
                    } else {
                        $set_top[$sk]['picture_url'] = '';
                        $set_top[$sk]['picture_url_original'] = '';
                        $set_top[$sk]['file_type'] = 1;
                    }
                    $set_item['isChecked'] = false;
                    $user_info = WdXcxUserAlbumPic::where('id', $set_item['id'])->find();
                    $set_item['nickname'] = $user_info->UserInfo['nickname'];
                    $set_item['upload_time'] = date('Y年m月d日 H:i', $user_info->getData('create_time'));
                }
                $lists[] = [
                    'collect_date' => '置顶',
                    'pictures' => $set_top,
                    'isChecked' => false,
                ];
            }


            // 首先获取所有不同的收藏日期，按日期倒序排列
            $dates = WdXcxUserAlbumPic::where('folder_id', $fid)
                ->whereNotIn('id', $set_top_ids)
                ->distinct(true)
                ->field('upload_date')
                ->order('upload_date desc')
                ->column('upload_date');
            // 分页处理日期
            $page = isset($param['page']) ? (int)$param['page'] : 1;
            $listRows = 10; // 每页显示的日期数量
            $dates = array_slice($dates, ($page - 1) * $listRows, $listRows);
            $result = [];
            foreach ($dates as $date) {
                // 获取该日期下的所有收藏图片
                $pictures = WdXcxUserAlbumPic::where('folder_id', $fid)
                    ->where('upload_date', $date)
                    ->where(function ($quey)use($param){
                        if(!empty($param['key'])){
                            $quey->where('pic_beizhu', 'like', '%'.$param['key'].'%');
                        }
                    })
                    ->order('id desc')
                    ->field('id, pic_id, set_top, pic_beizhu,user_id, create_time')
                    ->select()
                    ->each(function ($item) {
                        // 获取图片信息
                        $picture = WdXcxPic::where('id', $item->pic_id)->find();
                        if ($picture) {
                            $item->picture_url = $picture->TruePic;
                            $item->picture_url_original = removePicStyle($picture->TruePic);
                            $item->file_type = $picture->file_type;
                        } else {
                            $item->picture_url = '';
                            $item->picture_url_original = '';
                            $item->file_type = 1;
                        }
                        $item->isChecked = false;
                        $item->nickname = $item->UserInfo['nickname'];
                        $item->upload_time = date('Y年m月d日 H:i', $item->getData('create_time'));
                    });

                $lists[] = [
                    'collect_date' => $date,
                    'pictures' => $pictures,
                    'isChecked' => false,
                ];
            }
        }else{
            $lists = WdXcxUserAlbumPic::where('folder_id', $fid)
                ->where(function ($quey)use($param){
                    if(!empty($param['key'])){
                        $quey->where('pic_beizhu', 'like', '%'.$param['key'].'%');
                    }
                })
                ->order('set_top desc, set_top_time desc, id desc')
                ->field('id, pic_id, set_top, pic_beizhu,user_id, create_time')
                ->paginate(30)->each(function ($item){
                    $picture_url = '';
                    $file_type = 1;
                    if($item->picture){
                        $picture_url = $item->picture->TruePic;
                        $file_type = $item->picture->file_type;
                    }
                    $item->picture_url = $picture_url;
                    $item->picture_url_original = removePicStyle($picture_url);
                    $item->file_type = $file_type;
                    $item->isChecked = false;
                    $item->nickname = $item->UserInfo['nickname'];
                    $item->upload_time = date('Y年m月d日 H:i', $item->getData('create_time'));
                    unset($item->picture);
                });
            $lists = $lists->toArray()['data'];
        }
        $folder->upload_field = $folder->upload_field ? $folder->upload_field : [];
        //获取用户的上传链接code
        $upload_code = $this->getUploadCode($fid, $uid);
        return [
            'lists' => $lists,
            'show_upload_date' => $folder->show_upload_date,
            'folder' => $folder,
            'uploadd_code' => $upload_code,
            'share_str' => $share_str,
            'need_pwd' => $need_pwd,
            'user_pwd' => $user_pwd,
            'option_flag' => $option_flag,
        ];
    }


    public function getAlbumOnlyPicLists($param, $uid)
    {
        $fid = $param['fid'];
        if(empty($fid)){
            throwError('请选择相册');
        }
        $folder = WdXcxAlbumFolder::where('id', $fid)->where('folder_type', 2)->find();
        if(!$folder){
            throwError('指定相册不存在');
        }
        $lists = WdXcxUserAlbumPic::where('folder_id', $fid)
            ->where(function ($quey)use($param){
                if(!empty($param['key'])){
                    $quey->where('pic_beizhu', 'like', '%'.$param['key'].'%');
                }
            })
            ->order('set_top desc, set_top_time desc, id desc')
            ->field('id, pic_id, set_top, pic_beizhu,user_id, create_time')
            ->paginate(500)->each(function ($item){
                $picture_url = '';
                $file_type = 1;
                if($item->picture){
                    $picture_url = $item->picture->TruePic;
                    $file_type = $item->picture->file_type;
                }
                $item->picture_url = $picture_url;
                $item->picture_url_original = removePicStyle($picture_url);
                $item->file_type = $file_type;
                $item->isChecked = false;
                $item->nickname = $item->UserInfo['nickname'];
                $item->upload_time = date('Y年m月d日 H:i', $item->getData('create_time'));
                unset($item->picture);
            });
        $lists = $lists->toArray()['data'];

        return [
            'lists' => $lists,
        ];
    }

    private function getUploadCode($fid, $uid)
    {
        $info = WdXcxUserAlbumUploadCode::where([
            'fid' => $fid,
            'uid' => $uid,
        ])->find();
        if(!$info){
            do {
                // 生成10位随机字符串
                $code = $this->generateRandomString(10);
                // 检查该字符串是否已经存在于WdXcxUserAlbumUploadCode表中
                $exists =WdXcxUserAlbumUploadCode::where('upload_code', $code)->find();
            } while ($exists); // 如果存在则重新生成
            WdXcxUserAlbumUploadCode::create([
                'fid' => $fid,
                'uid' => $uid,
                'upload_code' => $code,
            ]);
            return $code;
        }
        return $info->upload_code;
    }

    public function getBatchUploadLink($fid, $uid)
    {
        $folder = WdXcxAlbumFolder::where('id', $fid)
            ->where('folder_type', 2)
            ->find();
        if(!$folder){
            throwError('产品不存在');
        }
        if($folder->uid != $uid && !$folder->checkFolderRule($uid)){
            throwError('您没有权限操作此产品');
        }
        $code = $this->getUploadCode($fid, $folder->uid);
        $url = 'https://pic.jfyuntu.com/assets/page/product-list.html?uploadd_code=' . urlencode($code);
        (new WdXcxUser())->ensureUploadPasswordColumns();
        $user = WdXcxUser::where('id', $folder->uid)->field('upload_pwd,upload_pwd_expire_time')->find();
        $expireTime = $user ? (int)$user->upload_pwd_expire_time : 0;
        $qrcode = '';
        try {
            $qrcode = Utils::createQrcode($url, '', true);
        } catch (\Throwable $e) {
            Log::error('getBatchUploadLink qrcode error: ' . $e->getMessage());
        }
        return [
            'upload_url' => $url,
            'url' => $url,
            'code' => $code,
            'qrcode' => $qrcode,
            'qrcode_url' => $qrcode,
            'qr_image' => $qrcode,
            'password' => $user && $user->upload_pwd ? $user->upload_pwd : '',
            'password_expire_time' => $expireTime,
            'upload_pwd_expire_time' => $expireTime,
        ];
    }

    public function resetBatchUploadLink($fid, $uid)
    {
        $folder = WdXcxAlbumFolder::where('id', $fid)
            ->where('folder_type', 2)
            ->where('uid', $uid)
            ->find();
        if(!$folder){
            throwError('产品不存在');
        }
        WdXcxUserAlbumUploadCode::where([
            'fid' => $fid,
            'uid' => $uid,
        ])->delete();
        return $this->getBatchUploadLink($fid, $uid);
    }

    private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**增加用户访问记录
     * @param $fid
     * @param $uid
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userVisitAlbum($fid, $uid)
    {
        $folder = WdXcxAlbumFolder::where('id', $fid)->find();
        if($folder){
            $has = WdXcxAlbumVisitRecord::where([
                'uid' => $uid,
                'fid' => $fid,
            ])->find();
            if(!$has){
                if($folder->uid != $uid){
                    WdXcxAlbumVisitRecord::create([
                        'uid' => $uid,
                        'fid' => $fid,
                    ]);
                    $folder->inc('visit_times')->update();
                }
            }else{
                $has->update_time = time();
                $has->save();
            }
        }
    }

    /**增加用户分享记录
     * @param $fid
     * @param $uid
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userShareAlbum($fid, $uid)
    {
        $folder = WdXcxAlbumFolder::where('id', $fid)->find();
        if($folder){
            $has = WdXcxAlbumShareRecord::where([
                'uid' => $uid,
                'fid' => $fid,
            ])->find();
            if(!$has){
                WdXcxAlbumShareRecord::create([
                    'uid' => $uid,
                    'fid' => $fid,
                ]);
                $folder->inc('share_times')->update();
            }
        }
    }

    public function uploadFileAlbum($params, $uid)
    {
        $need_check = WdXcxBase::where('uniacid', 1)->value('pic_check');
        $folder_info = WdXcxAlbumFolder::where('id', $params['pid'])->find();
        if (!$folder_info) {
            throwError('相册不存在');
        }
        if ($folder_info->folder_type != 2) {
            throwError('请选择相册');
        }
        if ($folder_info->uid != $uid) {
            if (!$folder_info->checkFolderRule($uid) || !$folder_info->editer_create) {
                throwError('您没有权限上传到此相册');
            }
        }
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
                $pic_album = [];
                $last_url = '';
                foreach ($data as $item){
                    $imageDetection = $this->weChatImageValidation($item['url']);
                    if($imageDetection["data"] != 0){ // 图片涉黄了
                        throwError("图片检测不通过，请重新上传");
                    }
                    $pic_album[] = [
                        'uniacid' => 1,
                        'user_id' => $uid,
                        'pic_id' => $item['pid'],
                        'folder_id' => $params['pid'],
                        'set_top_time' => time(),
                        'create_time' => time(),
                        'update_time' => time(),
                        'upload_date' => date('Y-m-d'),
                        'upload_field' => $params['upload_field'],
                    ];
                    $last_url = $item['url'];
                }
                $syncStartTime = time() - 5;
                WdXcxUserAlbumPic::insertAll($pic_album);
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

    /**设置与取消图片置顶操作
     * @param $params
     * @param $uid
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userChangePicSetTop($params, $uid)
    {
        $pic = WdXcxUserAlbumPic::where('id', $params['pic_id'])->find();
        if(!$pic){
            throwError('图片不存在');
        }
        $folder = WdXcxAlbumFolder::where('id', $pic->folder_id)->find();
        if (!$folder) {
            throwError('相册不存在');
        }
        if ($folder->uid != $uid && !$folder->checkFolderRule($uid)) {
            throwError('您没有权限操作此图片');
        }
        $pic->set_top = $pic->set_top ? 0 : 1;
        if($pic->set_top == 0){
            $pic->set_top_time = $pic->getData('create_time');
        }else{
            $pic->set_top_time = time();
        }
        $pic->save();
    }

    /**删除图片
     * @param $param
     * @param $uid
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userDeletePics($param, $uid)
    {
        $pic_ids = array_values(array_filter(array_map('intval', explode(',', $param['pic_id']))));
        if(empty($pic_ids)){
            throwError('请选择要删除的图片');
        }
        $pic_info = WdXcxUserAlbumPic::where('id', $pic_ids[0])->find();
        if(!$pic_info){
            throwError('图片不存在');
        }
        $folder_info = WdXcxAlbumFolder::where('id', $pic_info->folder_id)->find();
        if(!$folder_info){
            throwError('相册不存在');
        }
        if((int)$uid !== (int)$folder_info->uid){
            if(!$folder_info->checkFolderRule($uid) || (int)$folder_info->editer_delete_pic !== 1){
                throwError('您没有权限删除此图片');
            }
        }
        $pic_count = WdXcxUserAlbumPic::whereIn('id', $pic_ids)
            ->where('folder_id', $folder_info->id)
            ->count();
        if((int)$pic_count !== count($pic_ids)){
            throwError('您没有权限删除此图片');
        }
        $deletedSyncItems = [];
        Db::startTrans();
        try{
            foreach ($pic_ids as $pic_id){
                $pic_info = WdXcxUserAlbumPic::where('id', $pic_id)->find();
                if(!$pic_info || (int)$pic_info->folder_id !== (int)$folder_info->id){
                    throwError('您没有权限删除此图片');
                }
                //更新父级文件夹缩略图
                if($pic_info->picture->imgurl == $folder_info->getData('new_thumb')){
                    //查询文件夹下其他图片
                    $new_thumb = $this->getParentLastPic($pic_info->folder_id);
                    $this->updateParentThumbs($pic_info->folder_id, $new_thumb);
                }
                if($param['del_type'] == 1){ //删除图片  2是仅移出相册
                    $pic = WdXcxPic::where('id', $pic_info->pic_id)->find();
                    $pic->delete();
                }
                $deletedSyncItems[] = [
                    'pic_id' => (int)$pic_info->pic_id,
                    'relation_id' => (int)$pic_info->id,
                    'folder_id' => (int)$pic_info->folder_id,
                    'delete_resource' => (int)$param['del_type'] === 1,
                ];
                $pic_info->delete();
            }
        }catch (\Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
        if (!empty($deletedSyncItems)) {
            $bridge = new AiResourceBridgeService($this->app);
            foreach ($deletedSyncItems as $item) {
                $bridge->safeMarkPictureDeleted($folder_info->uid, $item['pic_id'], [
                    'b_folder_id' => $item['folder_id'],
                    'b_relation_id' => $item['relation_id'],
                    'external_product_id' => (string)$item['folder_id'],
                    'role' => 'album',
                    'delete_resource' => $item['delete_resource'],
                ]);
            }
        }
    }


    /**用户更新相册信息
     * @param $param
     * @param $uid
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userUpdateFolderSet($param, $uid)
    {
        $this->ensureProductStatusColumns();
        $folder_info = WdXcxAlbumFolder::where('id', $param['fid'])->find();
        if(!$folder_info){
            throwError('相册不存在');
        }
        $isOwner = (int)$folder_info->uid === (int)$uid;
        if (!$isOwner && !$folder_info->checkFolderRule($uid)) {
            throwError('您没有权限操作此相册');
        }
        if(isset($param['set_top'])){
            $folder_info->set_top = $param['set_top'] == '1' ? 1 : 0;
            if($folder_info->set_top == 1){
                $folder_info->set_top_time = time();
            }else{
                $folder_info->set_top_time = $folder_info->getData('create_time');
            }
        }
        if ($isOwner) {
            if($param['upload_field'] != $folder_info->getData('upload_field')){
                $folder_info->need_confirm = 1;
            }
            if (isset($param['show_connect'])) {
                $folder_info->show_connect = $param['show_connect'];
            }
            if (isset($param['folder_name'])) {
                $folder_info->folder_name = $param['folder_name'];
            }
            if (isset($param['other_share'])) {
                $folder_info->other_share = $param['other_share'];
            }
            if (isset($param['layout_type']) && $param['layout_type'] !== '') {
                $folder_info->layout_type = ((int)$param['layout_type'] === 2 ? 2 : 1);
            }
            if (isset($param['pic_layout']) && $param['pic_layout'] !== '') {
                $folder_info->pic_layout = ((int)$param['pic_layout'] === 1 ? 1 : 2);
            }
            if (isset($param['hide_detail_pictures']) && $param['hide_detail_pictures'] !== null) {
                $folder_info->hide_detail_pictures = (int)$param['hide_detail_pictures'] === 1 ? 1 : 0;
            }
            if (isset($param['sort_type'])) {
                $folder_info->sort_type = $param['sort_type'];
            }
            if (isset($param['show_upload_date'])) {
                $folder_info->show_upload_date = $param['show_upload_date'] ? 1 : 0;
            }
            if (isset($param['show_search'])) {
                $folder_info->show_search = $param['show_search'];
            }
            if (isset($param['upload_field'])) {
                $folder_info->upload_field = json_decode($param['upload_field'], true);
            }
            if (isset($param['editer_create'])) {
                $folder_info->editer_create = $param['editer_create'];
            }
            if (isset($param['editer_delete'])) {
                $folder_info->editer_delete = $param['editer_delete'];
            }
            if (isset($param['editer_delete_pic'])) {
                $folder_info->editer_delete_pic = $param['editer_delete_pic'];
            }
            if (isset($param['private_type'])) {
                $folder_info->private_type = $param['private_type'];
            }
        } else {
            if (isset($param['set_top']) || isset($param['folder_name']) || isset($param['other_share']) || isset($param['upload_field']) || isset($param['editer_create']) || isset($param['editer_delete']) || isset($param['editer_delete_pic']) || isset($param['private_type']) || isset($param['show_connect']) || isset($param['show_upload_date']) || isset($param['show_search']) || isset($param['sort_type']) || isset($param['layout_type']) || isset($param['pic_layout'])) {
                throwError('协作者不能修改相册设置');
            }
        }
        $folder_info->save();
    }

    /**收藏 / 增加图片大屏文件夹图片
     * @param $param
     * @param $uid
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userMovePictures($param, $uid)
    {
        $pic_ids = explode(',', $param['pic_ids']);
        if($param['fid'] > 0){
            $folder_info = WdXcxAlbumFolder::where('id', $param['fid'])->find();
            if(!$folder_info){
                throwError('相册不存在');
            }
            if($folder_info->folder_type != 2){
                throwError('请选择相册而非文件夹');
            }
            if($folder_info->uid != $uid){
                if(!$folder_info->checkFolderRule($uid) || !$folder_info->editer_create){
                    throwError('您没有权限操作此相册');
                }
            }
        }
        $user = WdXcxUser::where('id', $uid)->find();
        $limitSize = $user->UserCanUserPicSize;
        Db::startTrans();
        try{
            $totall_size = 0;
            $lastPicPath = ''; // 存储最后一张图片的路径
            $createdRelations = [];
            foreach ($pic_ids as $pic_id){
                $pic = WdXcxPic::where('id', $pic_id)->find();
                if(!$pic){
                    throwError('图片不存在');
                }
                if($param['fid'] == -1){ //收藏
                    $has = WdXcxUserCollectPics::where([
                        'pic_id' => $pic_id,
                        'uid' => $uid,
                    ])->find();
                    if(!$has){
                        WdXcxUserCollectPics::create([
                            'pic_id' => $pic_id,
                            'uid' => $uid,
                            'collect_date' => date('Y-m-d')
                        ]);
                    }
                }else{
                    $totall_size = $totall_size + $pic->getData('size');
                    $new_pic = new WdXcxPic();
                    $new_pic->save([
                        'uniacid' => $pic->uniacid,
                        'gid' => $pic->gid,
                        'pic_name' => $pic->pic_name,
                        'imgurl' => $pic->imgurl,
                        'size' => $pic->getData('size'),
                        'original_img' => $pic->getData('original_img'),
                        'type' => $pic->getData('type'),
                        'file_type' => $pic->file_type,
                        'uid' => $uid,
                    ]);
                    $relation = WdXcxUserAlbumPic::create([
                        'uniacid' => 1,
                        'user_id' => $uid,
                        'pic_id' => $new_pic->id,
                        'folder_id' => $param['fid'],
                        'set_top_time' => time(),
                        'create_time' => time(),
                        'update_time' => time(),
                        'upload_date' => date('Y-m-d', time()),
                    ]);
                    if ($relation) {
                        $createdRelation = WdXcxUserAlbumPic::where('id', $relation->id)->with(['picture'])->find();
                        if ($createdRelation) {
                            $createdRelations[] = $createdRelation;
                        }
                    }
                    // 记录最后一张图片的路径
                    if($pic->file_type == 1){
                        $lastPicPath = $pic->imgurl ?? ''; // 假设图片路径字段为 pic_url
                    }
                }
            }
            if($param['fid'] > 0 && $totall_size > $limitSize*1024*1024){
                throwError('您已超出最大存储空间');
            }
            if($lastPicPath && $param['fid'] > 0){
                $this->updateParentThumbs($param['fid'], $lastPicPath);
            }
        }catch (\Exception $exception){
            Db::rollback();
            throwError($exception->getMessage());
        }
        Db::commit();
        if (!empty($createdRelations)) {
            $bridge = new AiResourceBridgeService($this->app);
            foreach ($createdRelations as $relation) {
                $bridge->safeSyncAlbumRelation($uid, $relation, 'album');
            }
        }
    }

    /**更新图片备注
     * @param $param
     * @param $uid
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userSavePictureBeizhu($param, $uid)
    {
        if($param['pic_type'] == 1){
            $pic = WdXcxUserAlbumPic::where('id', $param['pic_id'])->find();
            if(!$pic){
                throwError('图片不存在');
            }
            $folder = WdXcxAlbumFolder::where('id', $pic->folder_id)->find();
            if(!$folder){
                throwError('文件夹不存在');
            }
            if($folder->uid != $uid){
                if(!$folder->checkFolderRule($uid)){
                    throwError('您没有权限操作此文件夹');
                }
            }
            $pic->pic_beizhu = $param['beizhu'];
            $pic->save();
        }elseif($param['pic_type'] == 3){
            $pic = WdXcxUserCollectPics::where('id', $param['pic_id'])->find();
            if(!$pic){
                throwError('图片不存在');
            }
            $pic->pic_beizhu = $param['beizhu'];
            $pic->save();
        }else{
            $pic = WdXcxPic::where('id', $param['pic_id'])->find();
            if(!$pic){
                throwError('图片不存在');
            }
            $pic->pic_beizhu = $param['beizhu'];
            $pic->save();
        }

    }

    public function userRenamePicture($param, $uid)
    {
        $picId = isset($param['pic_id']) ? (int)$param['pic_id'] : 0;
        $newName = isset($param['pic_name']) ? trim($param['pic_name']) : '';
        if ($picId <= 0) {
            throwError('请选择图片');
        }
        if ($newName === '') {
            throwError('图片名称不能为空');
        }
        $pic = WdXcxPic::where('id', $picId)->where('uid', $uid)->find();
        if (!$pic) {
            throwError('图片不存在');
        }
        $pic->pic_name = $newName;
        $pic->save();
    }

    /**用户举报相册文件夹
     * @param $param
     * @param $uid
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userReportAlbumFolder($param, $uid)
    {
        $folder = WdXcxAlbumFolder::where('id', $param['fid'])->find();
        if(!$folder){
            throwError('文件夹不存在');
        }
        $has = WdXcxUserReportAlbum::where([
            'fid' => $param['fid'],
            'uid' => $uid,
        ])->find();
        if($has){
            throwError('您已举报过此文件夹');
        }
        WdXcxUserReportAlbum::create([
            'fid' => $param['fid'],
            'uid' => $uid,
            'report_content' => $param['report'],
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    public function userResetShareLink($param, $uid)
    {
        $fid = $param['fid'];
        $redis = new Redis(GetRedisConf());
        $share_str = $redis->get('share_album_'.$fid);
        $redis->delete($share_str);
        $redis->delete('share_album_'.$fid);
    }

    /**修改文件夹密码
     * @param $param
     * @param $uid
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeFolderShowPwd($param, $uid)
    {
        $folder = WdXcxAlbumFolder::where('id', $param['fid'])->find();
        if(!$folder){
            throwError('文件夹不存在');
        }
        if($folder->uid != $uid){
            throwError('您没有权限操作此文件夹');
        }
        //检查密码是否为4位数字
        if($param['pwd']){
            if(!preg_match('/^[0-9]{4}$/', $param['pwd'])){
                throwError('密码格式错误');
            }
            if($param['pwd'] != $folder->show_pwd){
                WdXcxVisitFolderPwd::where('folder', $folder->id)->delete();
            }
        }else{
            throwError('请输入密码');
        }
        $folder->show_pwd = $param['pwd'];
        $folder->save();
    }

    public function userVisitFolderByPwd($param, $uid)
    {
        if(empty($param['fid'])){
            throwError('请选择文件夹');
        }
        $has = WdXcxVisitFolderPwd::where([
            'folder' => $param['fid'],
            'uid' => $uid,
        ])->find();
        if(!$has){
            WdXcxVisitFolderPwd::create([
                'folder' => $param['fid'],
                'uid' => $uid
            ]);
        }

    }

    /**
     * 获取产品详情
     * @param $product_id
     * @param int $uid
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductDetail($product_id, $uid = 0)
    {
        $this->ensureProductStatusColumns();
        $product = WdXcxAlbumFolder::where('id', $product_id)->where('folder_type', 2)->find();
        if (!$product) {
            throwError('产品不存在');
        }
        
        if ($product->private_type == 2 && $product->uid != $uid) {
             throwError('此内容为私有，请勿访问');
        }

        $pic_ids = $this->normalizeIdList($product->pic_ids);
        $product->pic_list = [];
        if (!empty($pic_ids)) {
            $picOrder = implode(',', $pic_ids);
            $product->pic_list = WdXcxPic::whereIn('id', $pic_ids)
                ->field('id, imgurl, pic_name, uniacid, file_type')
                ->orderRaw('FIELD(id, ' . $picOrder . ')')
                ->select()
                ->each(function($item){
                    $item->imgurl = $item->TruePic;
                    $item->picture_url = $item->imgurl;
                    $item->picture_url_original = removePicStyle($item->imgurl);
                });
        }

        $hideDetailPictures = (int)($product->hide_detail_pictures ?? 0) === 1;
        $product->hide_detail_pictures = $hideDetailPictures ? 1 : 0;
        $detail_pic_ids = $hideDetailPictures && (int)$product->uid !== (int)$uid
            ? []
            : $this->normalizeIdList($product->detail_pic_ids);
        $product->detail_pic_list = [];
        if (!empty($detail_pic_ids)) {
            $detailOrder = implode(',', $detail_pic_ids);
            $product->detail_pic_list = WdXcxPic::whereIn('id', $detail_pic_ids)
                ->field('id, imgurl, pic_name, uniacid, file_type')
                ->orderRaw('FIELD(id, ' . $detailOrder . ')')
                ->select()
                ->each(function($item){
                    $item->imgurl = $item->TruePic;
                    $item->picture_url = $item->imgurl;
                    $item->picture_url_original = removePicStyle($item->imgurl);
                });
        }

        $isCollect = 0;
        if ($uid) {
            $exists = WdXcxUserCollectAlbums::where('uid', $uid)->where('fid', $product_id)->find();
            if ($exists) {
                $isCollect = 1;
            }
        }
        $product->is_collect = $isCollect;

        $this->ensureBindUseridColumn();
        $bindIds = WdXcxProductCategoryBind::where('product_id', $product_id)->where('userid', $product->uid)->column('category_id');
        $directId = ($product->pid > 0) ? [$product->pid] : [];
        $allIds = array_unique(array_merge($bindIds ?: [], $directId));
        $product->category_ids = array_values($allIds);

        return $product;
    }

    public function getProductCategories($product_id, $uid)
    {
        $this->ensureBindUseridColumn();
        $product = WdXcxAlbumFolder::where('id', $product_id)->where('folder_type', 2)->find();
        if(!$product){
            throwError('产品不存在');
        }
        if($product->uid != $uid){
            throwError('无权限访问该产品分类');
        }
        $bindIds = WdXcxProductCategoryBind::where('product_id', $product_id)->where('userid', $uid)->column('category_id');
        $directId = ($product->pid > 0) ? [$product->pid] : [];
        $allIds = array_unique(array_merge($bindIds ?: [], $directId));
        if (empty($allIds)) {
            return [];
        }
        $lists = WdXcxAlbumFolder::whereIn('id', $allIds)
            ->where('folder_type', 1)
            ->where('uid', $uid)
            ->field('id, folder_name, new_thumb, create_time, sort')
            ->order('sort asc, id desc')
            ->select();
        return $lists;
    }

}
