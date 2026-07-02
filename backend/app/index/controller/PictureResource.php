<?php

namespace app\index\controller;

use app\BaseController;
use app\index\service\PictureResourceService;
use think\Exception;

class PictureResource extends BaseController
{
    /**图片库分类
     * @return \think\response\Json
     */
    public function getPicgroup()
    {
        $parame = request()->param();
        $data = (new PictureResourceService())->picGroups($parame);
        return json(['code' => 0, 'msg' => 'success', 'data' => $data]);
    }

    public function getGroupNames()
    {
        $parame = request()->param();
        $data = (new PictureResourceService())->groupNames($parame);
        return json(['code' => 0, 'msg' => 'success', 'data' => $data]);
    }

    /**图片库列表
     * @return \think\response\Json
     */
    public function getPicList()
    {
        $parame = request()->param();
        $data = (new PictureResourceService())->picList($parame);
        return json(['code' => 0, 'msg' => 'success', 'data' => $data]);
    }

    /**添加图库栏目
     * @return \think\response\Json
     */
    public function addGroup()
    {
        $parame = request()->param();
        try{
            (new PictureResourceService())->addGroup($parame);
        }catch (Exception $exception){
            return json(['code' => 1, 'msg' => $exception->getMessage()]);
        }
        return json(['code' => 0, 'msg' => '添加成功']);
    }

    /**编辑栏目名称
     * @return \think\response\Json
     */
    public function editGroup()
    {
        $parame = request()->param();
        try{
            (new PictureResourceService())->editGroup($parame);
        }catch (Exception $exception){
            return json(['code' => 1, 'msg' => $exception->getMessage()]);
        }
        return json(['code' => 0, 'msg' => '操作成功']);
    }

    /**删除栏目
     * @return \think\response\Json
     */
    public function deleteGroup()
    {
        $parame = request()->param();
        try{
            (new PictureResourceService())->deleteGroup($parame);
        }catch (Exception $exception){
            return json(['code' => 1, 'msg' => $exception->getMessage()]);
        }
        return json(['code' => 0, 'msg' => '操作成功']);
    }

    /**批量移动图片
     * @return \think\response\Json
     */
    public function movePictureGroup()
    {
        $parame = request()->param();
        try{
            (new PictureResourceService())->movePicGroup($parame);
        }catch (Exception $exception){
            return json(['code' => 1, 'msg' => $exception->getMessage()]);
        }
        return json(['code' => 0, 'msg' => '操作成功']);
    }

    /**批量删除图片
     * @return \think\response\Json
     */
    public function deletePictures()
    {
        $parame = request()->param();
        try{
            (new PictureResourceService())->deletePictures($parame);
        }catch (Exception $exception){
            return json(['code' => 1, 'msg' => $exception->getMessage()]);
        }
        return json(['code' => 0, 'msg' => '操作成功']);
    }

    /**本地上传图片
     * @return \think\response\Json
     */
    public function uploadImage()
    {
        $parame = request()->param();
        $parame['files'] = request()->file();
        $parame['flag'] = 1; //需要保存到图库
        try{
            $pictures = (new PictureResourceService())->uploadImage($parame);
        }catch (Exception $exception){
            return json(['code' => 1, 'msg' => $exception->getMessage()]);
        }
        return json(['code' => 0, 'msg' => '上传完成', 'data' => $pictures]);
    }

    /**提取网络图片
     * @return \think\response\Json
     */
    public function getRemoteImage()
    {
        $parame = request()->param();
        $parame['flag'] = 1; //需要保存到图库
        try{
            $data = (new PictureResourceService())->getRemoteImage($parame);
        }catch (Exception $exception){
            return json(['code' => 1, 'msg' => $exception->getMessage()]);
        }
        return json(['code' => 0, 'msg' => '提取完成', 'data' => $data]);
    }

    /**保存裁剪的图片
     * @return \think\response\Json
     */
    public function saveCutImage()
    {
        $parame = request()->param();
        $parame['flag'] = 1; //需要保存到图库
        $parame['files'] = request()->file();
        try{
            $data = (new PictureResourceService())->saveCutImage($parame);
        }catch (Exception $exception){
            return json(['code' => 1, 'msg' => $exception->getMessage()]);
        }
        return json(['code' => 0, 'msg' => '裁剪完成', 'data' => $data]);
    }

    /**图片重命名
     * @return \think\response\Json
     */
    public function renamePicName()
    {
        $parame = request()->param();
        try{
            (new PictureResourceService())->renamePicName($parame);
        }catch (Exception $exception){
            return json(['code' => 1, 'msg' => $exception->getMessage()]);
        }
        return json(['code' => 0, 'msg' => '重命名成功']);
    }

    /**
     * 栏目排序
     */
    public function sortGroupByDrag()
    {
        $parame = request()->param();
        try{
            (new PictureResourceService())->sortPicGroup($parame);
        }catch (Exception $exception){
            return json(['code' => 1, 'msg' => $exception->getMessage()]);
        }
        return json(['code' => 0, 'msg' => '操作成功']);
    }

    /**
     * 获取图库基础设置信息
     */
    public function getPicBaseSet()
    {
        $parame = request()->param();
        $set = (new PictureResourceService())->getPicBaseSet($parame);
        return json(['code' => 0, 'msg' => '获取成功', 'data' => $set]);
    }
}