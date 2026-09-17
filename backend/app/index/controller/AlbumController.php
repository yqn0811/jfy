<?php

namespace app\index\controller;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\album\WdXcxAlbumShareBind;
use app\common\model\user\WdXcxUserAlbumPic;
use app\common\service\album\AlbumService;
use app\common\service\RemoteObjectService;
use app\index\model\WdXcxPic;
use think\App;
use think\facade\View;

class AlbumController extends IndexBaseController
{
    private $album_service;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->album_service = new AlbumService($app);
    }

    public function cates()
    {
//        $this->checkUserRule(55);
//        $lists = $this->album_service->getCatesLists();
//        return View::fetch('album/cate', [
//            'lists' => $lists,
//        ]);
    }

    /**保存分类
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveCate()
    {
        $this->checkUserRule(55);
        $param = request()->param();
        if(empty($param['cate_name'])){
            $this->error('请填写名称');
        }
        if(!empty($param['fid'])){
            $info = WdXcxAlbumFolder::where('id', $param['fid'])->find();
            if(!$info){
                $this->error('指定内容不存在');
            }
        }else{
            $info = new WdXcxAlbumFolder();
        }
        $info->folder_name = $param['cate_name'];
        $info->uid = -1;
        $info->pid = isset($param['pid']) ? $param['pid'] : 0;
        $info->folder_type = isset($param['folder_type']) ? $param['folder_type'] : 1;
        $info->sort = isset($param['sort']) ? $param['sort'] : 1;
        $info->save();
        $this->success('保存成功');
    }

    /**编辑获取分类信息
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editCate()
    {
        $this->checkUserRule(55);
        $param = request()->param();
        $cate = WdXcxAlbumFolder::where([
            'uid' => -1,
            'id' => $param['fid']
        ])->find();
        $this->success('success', '', $cate);
    }

    /**删除分类
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delCate()
    {
//        $this->checkUserRule(55);
//        $param = request()->param();
//        $this->album_service->delCate($param);
//        $this->success('删除成功');
    }

    /**相册列表
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function albumLists()
    {
        $this->checkMenuPath(54, 56);
        $this->checkUserRule(56);
        $uid = input('uid') ? input('uid') : 0;
        $lists = $this->album_service->getAlbumLists(request()->param(), $uid);
        $key = input('key') ? input('key') : '';
        return View::fetch('album/album_lists', [
            'lists' => $lists,
            'cates' => '',
            'cate_id' => 0,
            'key' => $key
        ]);
    }

    /**获取相册图片列表
     * @return string
     */
    public function albumPicLists()
    {
        $this->checkMenuPath(54, 56);
        $this->checkUserRule(56);
        $lists = $this->album_service->albumPicLists(request()->param());
        $key = input('key') ? input('key') : '';
        return View::fetch('album/album_pic_lists', [
            'lists' => $lists,
            'cates' => '',
            'cate_id' => 0,
            'key' => $key
        ]);
    }

    /**删除 图片
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteAlbumPic()
    {
        $this->checkMenuPath(54, 56);
        $this->checkUserRule(56);
        $params = request()->param();
        if(empty($params['pic_id'])){
            $this->error('请选择图片');
        }
        $pic = WdXcxUserAlbumPic::where('id', $params['pic_id'])
            ->find();
        if(!$pic){
            $this->error('指定内容不存在');
        }
        $pic_data = WdXcxPic::where('id', $pic->pic_id)->find();
        if(!$pic_data){
            $this->error('图片不存在');
        }
        //删除源文件
        (new RemoteObjectService($this->app))->deleteRemoteObjectByPath(1, [$pic_data->imgurl]);
        //更新父级文件夹缩略图
        $pic_folder = WdXcxAlbumFolder::where('id', $pic->folder_id)->find();
        if(ltrim($pic->picture->imgurl, '/') == ltrim($pic_folder->getData('new_thumb'), '/')){
            //查询文件夹下其他图片
            $new_thumb = $this->album_service->getParentLastPic($pic->folder_id);
            $this->album_service->updateParentThumbs($pic->folder_id, $new_thumb);
        }
        $pic_data->force()->delete();
        $pic->force()->delete();
        $this->success('删除成功');
    }



    /**示例相册列表
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exampleAlbum()
    {
        $this->checkMenuPath(54, 55);
        $this->checkUserRule(55);
        $params = request()->param();
        $album_info = '';
        $album_id = 0;
        if(!empty($params['album_id'])){
            $album_info = WdXcxAlbumFolder::where('id', $params['album_id'])->find();
            if(!$album_info){
                $this->error('指定内容不存在');
            }
            $album_id = $params['album_id'];
        }
        $lists = $this->album_service->getAlbumLists($params, -1);
        $key = input('key') ? input('key') : '';
        return View::fetch('album/example_album_lists', [
            'lists' => $lists,
            'key' => $key,
            'album_info' => $album_info,
            'album_id' => $album_id
        ]);
    }

    /**示例相册图片列表
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exampleAlbumPic()
    {
        $this->checkMenuPath(54, 55);
        $this->checkUserRule(55);
        $params = request()->param();
        $album_id = isset($params['album_id']) ? $params['album_id'] : 0;
        $lists = $this->album_service->albumPicLists($params, -1);
        $key = input('key') ? input('key') : '';
        return View::fetch('album/example_album_pic_lists', [
            'lists' => $lists,
            'album_id' => $album_id,
            'cate_id' => 0,
            'key' => $key
        ]);
    }

    /**保存相册图片
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addAlbumPic()
    {
        $params = request()->param();
        if(empty($params['album_id'])){
            $this->error('请选择相册');
        }
        if(empty($params['pids'])){
            $this->error('请选择图片');
        }
        $album_info = WdXcxAlbumFolder::where('id', $params['album_id'])->find();
        if(!$album_info || $album_info['folder_type'] !=2){
            $this->error('指定内容不存在');
        }
        $pids = explode(',', $params['pids']);
        $this->album_service->saveAlbumPic($album_info, $pids, -1);
        $this->success('保存成功');
    }

    public function deleteExampleAlbumFolder()
    {
        $this->checkMenuPath(54, 55);
        $this->checkUserRule(55);
        $params = request()->param();
        if(empty($params['album_id'])){
            $this->error('请选择删除内容');
        }
        $info = WdXcxAlbumFolder::where([
            'id' => $params['album_id'],
            'uid' => -1
        ])->find();
        if(!$info){
            $this->error('指定内容不存在');
        }
        if($info->SonCount > 0){
            $this->error('请先删除子内容');
        }
        $info->delete();
    }

    /**删除图片
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteExampleAlbumPic()
    {
        $this->checkMenuPath(54, 55);
        $this->checkUserRule(55);
        $params = request()->param();
        if(empty($params['pic_id'])){
            $this->error('请选择图片');
        }
        $pic = WdXcxUserAlbumPic::where('id', $params['pic_id'])
            ->where('user_id', -1)
            ->find();
        if(!$pic){
            $this->error('指定内容不存在');
        }
        //更新父级文件夹缩略图
        $pic_folder = WdXcxAlbumFolder::where('id', $pic->folder_id)->find();
        if($pic->picture->imgurl == $pic_folder->getData('new_thumb')){
            //查询文件夹下其他图片
            $new_thumb = $this->album_service->getParentLastPic($pic->folder_id);
            $this->album_service->updateParentThumbs($pic->folder_id, $new_thumb);
        }
        $pic->delete();
        $this->success('删除成功');
    }



    /**新增编辑相册
     * @return string
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addAlbum()
    {
        $this->checkUserRule(56);
        $param = request()->param();
        $info = null;
        $allimg = '';
        if(!empty($param['album_id'])){
            $info = $this->album_service->editAlbum($param);
            $allimg = $info['album_pics'];
            $info = $info->toArray();
        }
        $cates = $this->album_service->getCatesLists(2);
        return View::fetch('album/add_album', [
            'info' => $info,
            'cates' => $cates,
            'allimg' => $allimg
        ]);
    }

    /**保存相册内容
     * @return void
     */
    public function saveAlbum()
    {
        $this->checkUserRule(56);
        $param = request()->param();
        $this->album_service->saveAlbum($param);
        $this->success('保存成功', Url('AlbumController/albumLists'));
    }

    /**审核相册状态
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeAlbumStatus()
    {
        $this->checkUserRule(56);
        $param = request()->param();
        if(empty($param['album_id']) || empty($param['check_status'])){
            $this->error('请选择内容');
        }
        $info = WdXcxAlbumFolder::where('id', $param['album_id'])->find();
        if(!$info){
            $this->error('指定内容不存在');
        }
        if($info->check_status != 0){
            $this->error('当前状态不可以操作');
        }
        $info->check_status = $param['check_status'] == 1 ? 1 : -1;
        $info->save();
        $this->success('操作成功');
    }

    /**删除用户相册
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteAlbum()
    {
        $this->checkUserRule(56);
        $params = request()->param();
        if(empty($params['album_id'])){
            $this->error('请选择删除内容');
        }
        $info = WdXcxAlbumFolder::where([
            'id' => $params['album_id'],
        ])->find();
        if(!$info){
            $this->error('指定内容不存在');
        }
        if($info->SonCount > 0){
            $this->error('请先删除子内容');
        }
        //删除绑定
        WdXcxAlbumShareBind::where('fid', $info->id)->delete();
        $info->delete();
        $this->success('删除成功');
    }
}