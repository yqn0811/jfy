<?php

namespace app\api\controller;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\album\WdXcxAlbumShareBind;
use app\common\service\album\WebUploadService;
use think\App;

class WebUploadApiController extends ApiBaseController
{
    private $upload_service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->upload_service = new WebUploadService($app);
    }

    /**获取上传文件夹信息
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function getAlbumInfo()
    {
        $param = $this->request->getMore([
            ['code', ''],
        ]);
        if(empty($param['code'])){
            throwError('相册参数不完整');
        }
        $this->result($this->upload_service->getWebAlbumInfo($param));
    }

    /**获取上传token
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function getAlbumUploadToken()
    {
        $param = $this->request->postMore([
            ['code', ''],
            ['password', ''],
        ]);
        if(empty($param['code'])){
            throwError('相册参数不完整');
        }
        $this->result($this->upload_service->getWebAlbumUploadToken($param));
    }

    /**获取上传文件夹的信息
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function getUploadFolderInfo()
    {
        $param = $this->request->getMore([
            ['fid', ''],
        ]);
        if(empty($param['fid'])){
            throwError('相册参数不完整');
        }
        $this->result($this->upload_service->getUploadFolderInfo($param, request()->userID(), request()->webUploadFolderID()));
    }

    public function userUploadFolderPic()
    {
        $files = $this->request->file();
        if(empty($files)){
            throwError('请选择上传内容');
        }
        $pid = $this->request->post('fid', 0);
        if(!$pid){
            throwError('请选择相册');
        }
        if((int)$pid !== (int)request()->webUploadFolderID()){
            throwError('上传凭证与相册不匹配');
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
        $file_type = (int)$this->request->post('file_type', 1);
        if(!in_array($file_type, [1, 2])){
            throwError('文件类型不正确');
        }
        $upload_field = $this->resolveUploadField($this->request->post());
        $storage_file_type = 1;
        $result = $this->upload_service->uploadFileAlbum([
            'files' => $files,
            'file_type' => $storage_file_type,
            'upload_field' => $upload_field,
            'pid' => $pid,
            'original_names' => $this->getUploadOriginalNames($this->request->post()),
        ], $uid);
        $this->result($result);
    }

    private function resolveUploadField($param)
    {
        $raw = strtolower(trim((string)($param['upload_field'] ?? ($param['image_role'] ?? ''))));
        if (in_array($raw, ['detail', 'detail_chart', 'detailchart', 'detail_pic', 'detail_pic_ids', '2'], true)) {
            return 'detail_chart';
        }
        if ((int)($param['file_type'] ?? 1) === 2) {
            return 'detail_chart';
        }
        return 'color_chart';
    }

    private function getUploadOriginalNames($param)
    {
        $names = [];
        foreach (['original_name', 'filename', 'file_name', 'name'] as $field) {
            if (!isset($param[$field]) || $param[$field] === '') {
                continue;
            }
            $value = $param[$field];
            if (is_array($value)) {
                foreach ($value as $index => $name) {
                    if ($name !== '') {
                        $names[$index] = (string)$name;
                    }
                }
            } elseif (empty($names)) {
                $names['default'] = (string)$value;
            }
        }
        return $names;
    }






}
