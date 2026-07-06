<?php

namespace app\api\controller;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\album\WdXcxAlbumShareBind;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserAlbumUploadCode;
use app\common\service\album\AiResourceBridgeService;
use app\common\service\album\AlbumService;
use think\App;

class AlbumApiController extends ApiBaseController
{
    private $album_service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->album_service = new AlbumService($app);
    }

    /**创建产品/分类
     * @return void
     */
    public function createAlbumFolder()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['folder_name', ''],
            ['folder_type', ''], // 1:分类 2:产品
            ['folder_desc', ''],
            ['new_thumb', ''],//封面图
            ['private_type', 1], // 1:公开 2:私密 4:仅分享可见
            ['layout_type', 1], // 1:双列 2:单列
            ['visible_type', 1],
            ['pic_ids', []], // 花色图
            ['detail_pic_ids', []], // 详情图
        ]);
        if ($param['folder_type'] == 2 && empty($param['pic_ids'])) {
            throwError('请选择花色图');
        }
        $info = $this->album_service->createAlbumFolder($param, request()->userID());
        $this->result($info, 0, '创建成功');
    }

    /**编辑产品/分类
     * @return void
     */
    public function editAlbumFolder()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['category_ids', []], // 支持修改分类
            ['folder_name', ''],
            ['folder_desc', ''],
            ['new_thumb', ''],
            ['private_type', null], // 1:公开 2:私密 4:仅分享可见
            ['layout_type', null], // 1:双列 2:单列
            ['pic_layout', null],
            ['visible_type', null],
            ['pic_ids', null], 
            ['detail_pic_ids', null],
        ]);
        if(empty($param['fid'])){
            throwError('请选择分类');
        }
        $this->album_service->editAlbumFolder($param, request()->userID());
        $this->result([], 0, '修改成功');
    }

    /**添加图片到产品
     * @return void
     */
    public function addAlbumPics()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['pic_ids', []],
        ]);
        if(empty($param['fid'])){
            throwError('请选择产品');
        }
        if(empty($param['pic_ids'])){
            throwError('请选择图片');
        }
        $this->album_service->addAlbumPics($param, request()->userID());
        $this->result([], 0, '添加成功');
    }

    /**设置产品排序
     * @return void
     */
    public function setAlbumSort()
    {
        $param = $this->request->postMore([
            ['sort_data', []],
        ]);
        $this->album_service->setAlbumSort($param, request()->userID());
        $this->result([], 0, '设置成功');
    }

    /**设置图片排序
     * @return void
     */
    public function setAlbumPicSort()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['sort_data', []],
        ]);
        if(empty($param['fid'])){
            throwError('请选择产品');
        }
        $this->album_service->setAlbumPicSort($param, request()->userID());
        $this->result([], 0, '设置成功');
    }

    /**获取所有产品列表（用于选择添加）
     * @return void
     */
    public function getAllProducts()
    {
        $this->result($this->album_service->getAllProducts(request()->userID()));
    }

    /**批量添加/移除产品到分类
     * @return void
     */
    public function addProductsToCategory()
    {
        $param = $this->request->postMore([
            ['fid', 0], // Changed from cate_id to fid to match frontend
            ['product_ids', []],
        ]);
        $this->album_service->addProductsToCategory($param['fid'], $param['product_ids'], request()->userID());
        $this->result([], 0, '操作成功');
    }

    public function addProductToCategories()
    {
        $param = $this->request->postMore([
            ['product_id', 0],
            ['category_ids', []],
        ]);
        if(empty($param['product_id'])){
            throwError('请选择产品');
        }
        $this->album_service->setProductCategories($param['product_id'], $param['category_ids'], request()->userID());
        $this->result([], 0, '操作成功');
    }

    public function updateProductStatus()
    {
        $param = $this->request->postMore([
            ['id', 0],
            ['is_hot', null],
            ['private_type', null],
            ['pic_layout', null],
            ['new_thumb', null],
        ]);
        if(empty($param['id'])){
            throwError('请选择产品');
        }
        $this->album_service->updateProductStatus($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    public function getBatchUploadLink()
    {
        $param = $this->request->getMore([
            ['fid', 0],
        ]);
        if(empty($param['fid'])){
            throwError('请选择产品');
        }
        $this->result($this->album_service->getBatchUploadLink($param['fid'], request()->userID()));
    }

    public function resetBatchUploadLink()
    {
        $param = $this->request->postMore([
            ['fid', 0],
        ]);
        if(empty($param['fid'])){
            throwError('请选择产品');
        }
        $this->result($this->album_service->resetBatchUploadLink($param['fid'], request()->userID()), 0, '重置成功');
    }

    public function saveBatchUploadPassword()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['upload_pwd', ''],
            ['upload_pwd_expire_time', 0],
            ['upload_enabled', 0],
        ]);
        if(empty($param['fid'])){
            throwError('请选择产品');
        }
        $folder = WdXcxAlbumFolder::where('id', $param['fid'])
            ->where('folder_type', 2)
            ->find();
        if(!$folder){
            throwError('产品不存在');
        }
        $uid = request()->userID();
        if($folder->uid != $uid){
            throwError('您没有权限操作此产品');
        }
        $password = trim((string)$param['upload_pwd']);
        $uploadEnabled = (int)$param['upload_enabled'] === 1 ? 1 : 0;
        if($uploadEnabled && $password === ''){
            throwError('开启访问密码时请设置密码');
        }
        if($password !== '' && !preg_match('/^[A-Za-z0-9]{4}$/', $password)){
            throwError('上传密码格式错误');
        }
        (new WdXcxUser())->ensureUploadPasswordColumns();
        WdXcxUserAlbumUploadCode::ensureUploadEnabledColumn();
        $code = $this->album_service->getBatchUploadLink($folder->id, $folder->uid)['code'];
        WdXcxUserAlbumUploadCode::where([
            'fid' => $folder->id,
            'uid' => $folder->uid,
            'upload_code' => $code,
        ])->update([
            'upload_enabled' => $uploadEnabled,
        ]);
        $expireTime = $uploadEnabled ? max(0, (int)$param['upload_pwd_expire_time']) : 0;
        if($uploadEnabled){
            WdXcxUser::where('id', $folder->uid)->update([
                'upload_pwd' => $password,
                'upload_pwd_expire_time' => $expireTime,
            ]);
        }else{
            $password = '';
        }
        $this->result([
            'upload_enabled' => $uploadEnabled,
            'access_enabled' => $uploadEnabled,
            'password' => $password,
            'password_expire_time' => $expireTime,
            'upload_pwd_expire_time' => $expireTime,
        ], 0, '保存成功');
    }

    public function deleteAlbumFolder()
    {
        $param = $this->request->postMore([
            ['fid', ''],
            ['del_type', 1],
        ]);
        if(empty($param['fid'])){
            throwError('请选择对象');
        }
        if(empty($param['del_type']) || !in_array($param['del_type'], [1, 2])){
            throwError('请选择删除方式');
        }
        $this->album_service->deleteAlbumFolder($param, request()->userID());
        $this->result([], 0, '删除成功');
    }

    /**获取产品、分类列表
     * @return void
     */
    public function getAlbumFolderLists()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['key', ''],
            ['share_uid', 0],
            ['link_share_str', ''],
            ['folder_type', 0],
            ['target_uid', 0],
            ['target_user_id', 0],
            ['limit', 10],
            ['page', 1],
        ]);
        $this->result($this->album_service->getAlbumFolderLists($param, request()->userID()));
    }

    /**获取展示选择使用的分类列表  不含分页 带收藏
     * @return void
     */
    public function getShowAlbumFolderLists()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['target_uid', 0],
        ]);
        $this->result($this->album_service->getShowAlbumFolderLists($param, request()->userID()));
    }

    /**获取产品图片列表
     * @return void
     */
    public function getAlbumPicLists()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['key', ''],
            ['link_share_str', ''],
            ['share_uid', 0],
            ['page', 1],
        ]);
        $this->result($this->album_service->getAlbumPicLists($param, request()->userID()));
    }

    public function getAlbumOnlyPicLists()
    {
        $param = $this->request->getMore([
            ['fid', 0],
            ['key', ''],
            ['page', 1],
        ]);
        $this->result($this->album_service->getAlbumOnlyPicLists($param, request()->userID()));
    }

    /**用户访问产品
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userVisitAlbum()
    {
        $param = $this->request->postMore([
            ['fid', 0],
        ]);
        if(!empty($param['fid'])){
            $this->album_service->userVisitAlbum($param['fid'], request()->userID());
        }
        $this->result([], 0, '访问成功');
    }

    /**用户分享产品
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userShareAlbum()
    {
        $param = $this->request->postMore([
            ['fid', 0],
        ]);
        if(!empty($param['fid'])){
            $this->album_service->userShareAlbum($param['fid'], request()->userID());
        }
        $this->result([], 0, '分享成功');
    }

    /**上传文件到相册
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userUploadFileAlbum()
    {
        $files = $this->request->file();
        if(empty($files)){
            throwError('请选择上传内容');
        }
        $pid = $this->request->post('pid', 0);
        $file_type = $this->request->post('file_type', 1);
        $upload_field = $this->request->post('upload_field', '');
        if(!$pid){
            throwError('请选择相册');
        }
        if(!in_array($file_type, [1, 2])){
            throwError('请选择正确的文件类型');
        }
        $uid = request()->userID();
        $folder = WdXcxAlbumFolder::where([
            'id' => $pid,
            'folder_type' => 2
        ])->find();
        if(!$folder){
            throwError('请选择正确的相册');
        }
        if($folder->uid != $uid){
            $parent_ids = $folder->ParentIds;
            $has_bind = WdXcxAlbumShareBind::where('bind_uid', $uid)
                ->whereIn('fid', $parent_ids)
                ->find();
            if(!$has_bind){
                throwError('您没有权限访问此相册');
            }
        }
        $result = $this->album_service->uploadFileAlbum([
            'files' => $files,
            'file_type' => $file_type,
            'pid' => $pid,
            'upload_field' => $upload_field
        ], $uid);
        $this->result($result['data'], 0, $result['msg']);
    }





    /**获取评价分类
     * @return void
     */
    public function getEvaluateCateLists()
    {
        $this->result($this->album_service->getEvaluateCateLists(1));
    }

    /**获取口碑分类
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function getEvaluateCate()
    {
        $this->result($this->album_service->getEvaluateCateLists());
    }

    /**用户提交评价口碑
     * @return void
     */
    public function userSubmitEvaluateInfo()
    {
        $param = $this->request->postMore([
            ['purpose', ''],
            ['evaluate_content', ''],
        ]);
        if(empty($param['purpose']) || empty($param['evaluate_content'])){
            $this->result([], 1, '请填写完整');
        }
        $this->album_service->userSubmitEvaluateInfo($param, request()->userID());
        $this->result([], 0, '提交成功');
    }

    /**获取用户提交评价
     * @return void
     */
    public function getUserSubmitEvaluate()
    {
        $param = $this->request->getMore([
            ['purpose', ''],
            ['page', 1],
        ]);
        $this->result($this->album_service->getUserSubmitEvaluate($param));
    }

    /**改变图片置顶状态
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userChangePicSetTop()
    {
        $param = $this->request->getMore([
            ['pic_id', 0],
        ]);
        if(empty($param['pic_id'])){
            throwError('请选择图片');
        }
        $this->album_service->userChangePicSetTop($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    public function userDeletePic()
    {
        $param = $this->request->postMore([
            ['pic_id', ''],
            ['del_type', 1],
        ]);
        if(empty($param['pic_id'])){
            throwError('请选择要删除的图片');
        }
        if(empty($param['del_type']) || !in_array($param['del_type'], [1, 2])){
            throwError('请选择正确的删除类型');
        }
        $this->album_service->userDeletePics($param, request()->userID());
        $this->result([], 0, '删除成功');
    }

    /**修改文件夹信息
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userUpdateFolderSet()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['folder_name', ''],
            ['folder_type', ''],
            ['show_connect', ''],
            ['set_top', 0],
            ['layout_type', ''],
            ['pic_layout', ''],
            ['other_share', ''],
            ['sort_type', ''],
            ['show_upload_date', ''],
            ['show_search', ''],
            ['upload_field', ''],
            ['editer_create', ''],
            ['editer_delete_pic', ''],
            ['editer_delete', ''],
            ['private_type', 1],
        ]);
        if(empty($param['fid'])){
            throwError('请选择文件夹');
        }
        $this->album_service->userUpdateFolderSet($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**用户移动图片到指定文件夹 或收藏
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userMovePictures()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['pic_ids', ''],
        ]);
        if(empty($param['fid']) || empty($param['pic_ids'])){
            throwError('请选择要移动的图片或相册');
        }
        $this->album_service->userMovePictures($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**用户保存图片备注
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userSavePictureBeizhu()
    {
        $param = $this->request->postMore([
            ['pic_id', 0],
            ['beizhu', ''],
            ['pic_type', 1],
        ]);
        if(empty($param['pic_id'])){
            throwError('请选择图片');
        }
        $this->album_service->userSavePictureBeizhu($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    public function userRenamePicture()
    {
        $param = $this->request->postMore([
            ['pic_id', 0],
            ['pic_name', ''],
        ]);
        if (empty($param['pic_id'])) {
            throwError('请选择图片');
        }
        if ($param['pic_name'] === '') {
            throwError('图片名称不能为空');
        }
        $this->album_service->userRenamePicture($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**用户举报相册或文件夹
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userReportAlbumFolder()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['report', ''],
        ]);
        if(empty($param['fid']) || empty($param['report'])){
            throwError('请选择要举报的相册或文件夹');
        }
        $this->album_service->userReportAlbumFolder($param, request()->userID());
        $this->result([], 0, '举报成功');
    }

    /**用户重置分享链接
     * @return void
     */
    public function userResetShareLink()
    {
        $param = $this->request->postMore([
            ['fid', 0],
        ]);
        $this->album_service->userResetShareLink($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**修改密码
     * @return void
     */
    public function changeFolderShowPwd()
    {
        $param = $this->request->postMore([
            ['fid', 0],
            ['pwd', ''],
        ]);
        $this->album_service->changeFolderShowPwd($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    public function userVisitFolderByPwd()
    {
        $param = $this->request->postMore([
            ['fid', 0],
        ]);
        $this->album_service->userVisitFolderByPwd($param, request()->userID());
        $this->result([], 0, '操作成功');
    }

    /**
     * 获取产品详情
     */
    public function getProductDetail()
    {
        $param = $this->request->postMore([
            ['fid', 0],
        ]);
        if(empty($param['fid'])){
            throwError('请选择产品');
        }
        $info = $this->album_service->getProductDetail($param['fid'], request()->userID());
        $this->result($info);
    }

    public function getProductCategories()
    {
        $param = $this->request->postMore([
            ['product_id', 0],
        ]);
        if(empty($param['product_id'])){
            throwError('请选择产品');
        }
        $info = $this->album_service->getProductCategories($param['product_id'], request()->userID());
        $this->result($info);
    }

    public function userChangeProductSetTop()
    {
        $param = $this->request->postMore([
            ['product_id', 0],
            ['is_top', 1],
        ]);
        if(empty($param['product_id'])){
            throwError('请选择产品');
        }
        if ($param['is_top'] == 1) {
            $this->album_service->setProductTop($param['product_id'], request()->userID());
        } else {
            $this->album_service->unsetProductTop($param['product_id'], request()->userID());
        }
        $this->result([], 0, '操作成功');
    }

    public function listAiResources()
    {
        $param = $this->request->getMore([
            ['page', 1],
            ['page_size', 30],
            ['keyword', ''],
            ['category_id', ''],
        ]);
        $this->result((new AiResourceBridgeService($this->app))->listResources(request()->userID(), $param));
    }

    public function importAiResource()
    {
        $param = $this->request->postMore([
            ['resource_id', 0],
            ['role', 'cover'],
        ]);
        $this->result((new AiResourceBridgeService($this->app))->importResource(request()->userID(), $param['resource_id'], $param['role']), 0, '导入成功');
    }



    /**删除分类
     */
    public function deleteCategory()
    {
        $param = $this->request->postMore([
            ['category_id', 0],
        ]);
        if(empty($param['category_id'])){
            throwError('请选择分类');
        }
        $this->album_service->deleteCategory($param['category_id'], request()->userID());
        $this->result([], 0, '删除成功');
    }
}
