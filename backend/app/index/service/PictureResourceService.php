<?php

namespace app\index\service;

use app\index\model\WdXcxPic;
use app\index\model\WdXcxPicgroup;
use app\index\service\upload\UploadService;
use think\Exception;

class PictureResourceService
{
    protected $gid = 0; //栏目id
    protected $pageSize = 18;  //每页数量
    protected $page = 1; //页码
    protected $shopId = 0;

    public function __construct()
    {
//        $this->shopId = Cookie::get('venue_id') ?? 0;
    }

    /**获取图片列表
     * @param $condition
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function picList($parame)
    {
        $condition = [];
        $condition['uniacid'] = $parame['uniacid'];
        //栏目id
        if(!empty($parame['gid'])){
            if($parame['gid'] != -1){ //-1 为全部
                $gids = WdXcxPicgroup::childGroups($parame['gid']);
                array_push($gids, $parame['gid']);
                $condition['gid'] = implode(',', $gids);
            }
        }
        //图片名称
        if(!empty($parame['pic_name'])){
            $condition['pic_name'] = ['like', '%'.$parame['pic_name'].'%'];
        }
        //上传日期
        if(!empty($parame['start_time'])){
            $condition['create_time'] = ['>', strtotime($parame['start_time'])];
        }
        if(!empty($parame['end_time'])){
            $condition['create_time'] = ['<', strtotime($parame['end_time'] . ' 23:59:59')];
        }
        if(!empty($parame['start_time']) && !empty($parame['end_time'])){
            $condition['create_time'] = ['between', [strtotime($parame['start_time']), strtotime($parame['end_time'] . ' 23:59:59')]];
        }
        if ($this->shopId) {
            $condition['shop_id'] = $this->shopId;
        }
        //页码数据
        $pageData = [
            'page' => !empty($parame['page']) ? $parame['page'] : $this->page,
            'pageSize' => !empty($parame['page_size']) ? $parame['page_size'] : $this->pageSize,
            'picLimit' => (new UploadService($parame['uniacid']))->getSingleFileSize(),
        ];
        return WdXcxPic::lists($condition, $pageData);
    }

    //获取栏目
    public function picGroups($parame)
    {
        $condition = [];
        $condition['uniacid'] = $parame['uniacid'];
        $condition['type'] = $parame['type'] ?? 0;
        if(!empty($parame['key'])){
            $condition['name'] = ['like', '%'.$parame['key'].'%'];
        }
        if ($this->shopId) {
            $condition['shop_id'] = $this->shopId;
        }
        return WdXcxPicgroup::groupLists($condition);
    }

    //获取栏目名称
    public function groupNames($parame)
    {
        if ($this->shopId) {
            $parame['shop_id'] = $this->shopId;
        }
        return WdXcxPicgroup::groupNames($parame);
    }


    //添加栏目
    public function addGroup($parame)
    {
        if(empty($parame['group_name'])){
            throw new Exception('参数不完整');
        }
        if ($this->shopId) {
            $parame['shop_id'] = $this->shopId;
        }
        if(!$this->checkUrlAdmin() && empty($parame['uniacid'])){
            throw new Exception('参数不完整');
        }
        try{
            WdXcxPicgroup::addGroup($parame);
        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }

    }

    //编辑栏目名称
    public function editGroup($parame)
    {
        if(empty($parame['group_name']) || empty($parame['gid'])){
            throw new Exception('参数不完整');
        }
        if(!$this->checkUrlAdmin() && empty($parame['uniacid'])){
            throw new Exception('参数不完整');
        }
        try{
            WdXcxPicgroup::editGroup($parame);
        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }
    }

    //删除栏目
    public function deleteGroup($parame)
    {
        if(empty($parame['gid'])){
            throw new Exception('参数不完整');
        }
        if(!$this->checkUrlAdmin() && empty($parame['uniacid'])){
            throw new Exception('参数不完整');
        }
        try{
            WdXcxPicgroup::deleteGroup($parame);
        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }
    }

    //批量移动图片
    public function movePicGroup($parame)
    {
        if(empty($parame['pic_ids']) || empty($parame['gid'])){
            throw new Exception('参数不完整');
        }
        if(!$this->checkUrlAdmin() && empty($parame['uniacid'])){
            throw new Exception('参数不完整');
        }
        $parame['pic_ids'] = explode(',', $parame['pic_ids']);
        if(count($parame['pic_ids']) == 0){
            throw new Exception('请选择需要移动的图片');
        }
        try{
            WdXcxPic::movePictureGroup($parame);
        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }
    }

    //批量删除图片
    public function deletePictures($parame)
    {
        if(empty($parame['pic_ids'])){
            throw new Exception('参数不完整');
        }
        if(!$this->checkUrlAdmin() && empty($parame['uniacid'])){
            throw new Exception('参数不完整');
        }
        $parame['pic_ids'] = explode(',', $parame['pic_ids']);
        if(count($parame['pic_ids']) == 0){
            throw new Exception('请选择需要删除的图片');
        }
        try{
            WdXcxPic::deletePictures($parame);
        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }
    }

    //上传图片
    public function uploadImage($parame)
    {
        if(count($parame['files']) == 0){
            throw new Exception('请选择需要上传的图片');
        }
        $parame['gid'] = $parame['gid'] < 0 ? 0 : $parame['gid'];
        if ($this->shopId) {
            $parame['shop_id'] = $this->shopId;
        }
        try{
            $data = (new UploadService($parame['uniacid']))->uploadImages($parame);
        }catch (\Exception $exception){
            dd($exception);
            throw new Exception($exception->getMessage());
        }

        if(input('from')){
            if(!empty($data)){
                foreach ($data as $key => $value){
                    $data[$key]['url'] = remote($parame['uniacid'], $value['url'], 1);
                }
            }
            $data = json_encode($data);
        }
        return $data;
    }

    //获取网络图片
    public function getRemoteImage($parame)
    {
        $remote_url = $parame['remote_url'];
        if(!$remote_url){
            throw new Exception('请输入需要提取的图片地址');
        }
        if(!preg_match('/.*(\.png|\.jpg|\.jpeg|\.gif|\.PNG|\.JPG|\.JPEG)$/', $remote_url)){
            throw new Exception('网络图片地址不符合要求');
        }
        $url_size_info = getimagesize($remote_url);
        if(!$url_size_info){
            throw new Exception('获取网络图片失败');
        }
        if ($this->shopId) {
            $parame['shop_id'] = $this->shopId;
        }
        $parame['info'] = $url_size_info;
        try{
            $data = (new UploadService($parame['uniacid']))->uploadRemoteSource($parame);
        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }
        return $data;
    }

    //裁剪图片
    public function saveCutImage($parame)
    {
        if(count($parame['files']) == 0){
            throw new Exception('请提交需要保存的图片');
        }
        if ($this->shopId) {
            $parame['shop_id'] = $this->shopId;
        }
        try{
            $data = (new UploadService($parame['uniacid']))->uploadCutImages($parame);
        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }
        return $data;

    }

    //图片重命名
    public function renamePicName($parame)
    {
        if(empty($parame['pid']) || empty($parame['new_name'])){
            throw new Exception('参数不完整');
        }
        $new_name = $parame['new_name'];
        if(!preg_match('/.*(\.png|\.jpg|\.jpeg|\.gif|\.PNG|\.JPG|\.JPEG)$/', $new_name)){
            throw new Exception('新图片名称不符合要求');
        }
        $pic = WdXcxPic::find($parame['pid']);
        if($pic->pic_name){
            if(substr($pic->pic_name, -4) != substr($new_name, -4)){
                throw new Exception('新图片名称格式与原名称不匹配');
            }
        }
        $pic->pic_name = $new_name;
        $pic->save();
    }

    /**
     * 栏目拖拽排序
     */
    public function sortPicGroup($parame)
    {
        if(empty($parame['group_ids'])){
            throw new Exception('参数不完整');
        }
        $ids = explode(',', rtrim($parame['group_ids'], ','));
        if(count($ids) < 2){
            throw new Exception('操作异常');
        }
        $gid_sort = [];
        $sort = 200;
        for($i=0; $i<count($ids); $i++){
            array_push($gid_sort, $sort-$i);
        }
        foreach ($ids as $key => $item){
            WdXcxPicgroup::where('id', $item)->update(['sort' => $gid_sort[$key]]);
        }


    }

    /**获取图库基础设置
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPicBaseSet($param)
    {
        $size = (new UploadService($param['uniacid']))->getSingleFileSize();
        return [
            'pic_size' => $size,
        ];
    }

    private function checkUrlAdmin()
    {
        return strpos(request()->url(), '/admin/') === 0;
    }
}