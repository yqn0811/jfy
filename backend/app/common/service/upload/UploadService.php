<?php
namespace app\common\service\upload;
use app\common\service\bridge\JiafangyunEntitlementSyncService;
use app\common\model\user\WdXcxUser;
use app\common\model\WdXcxBase;
use think\Exception;

/**
 * Created by : PhpStorm
 * User: D0065
 * Date: 2022/1/15
 * Time: 14:22
 */

class UploadService
{
    public $REMOTE_TYPE = [
        '1' => LocalService::class,
        '2' => QiniuService::class,
        '3' => AliOssService::class,
        '4' => TenOssService::class,
    ];
    protected $uniacid;
    public $config;
    protected $img_ext = ['jpg','png','gif','jpeg'];
    protected $video_ext = ['avi','wmv','mpeg','mp4','m4v','mov','asf','flv','f4v','rmvb','rm','3gp','vob'];
    protected $img_size = 2097152; //2M   2*1024*1024
    protected $video_size = 104857600; //100M   100*1024*1024
    private static $syncedEntitlementUsers = [];


    public function __construct($uniacid)
    {
        $this->uniacid = $uniacid;
        $this->config = getRemoteConfig($uniacid);
        $this->img_size = $this->getSingleFileSize();
    }

    //上传网络图片
    public function uploadRemoteSource($parame)
    {
        $source = @file_get_contents($parame['remote_url']);
        if(!$source){
            throw new Exception('获取远程图片失败');
        }
        $this->checkUpImagesSize(strlen($source));
        $parame['source'] = $source;
        $class = new $this->REMOTE_TYPE[$this->config['remote']]($this->uniacid, $this->config);
        $images = $class->upLoadRemote($parame);
        if(count($images) > 0){
            foreach ($images as $k => $item){
                $images[$k]['url'] = remote($this->uniacid, $item['url'], 1);
            }
        }
        return $images;
    }

    /**上传服务器文件到远程附件
     * @param $param (本地路径，文件名称)
     * @return void
     */
    public function uploadRemoteFromLocal($param)
    {
        $source = @file_get_contents($param['remote_url']);
        if(!$source){
            throw new Exception('获取图片失败');
        }
        $param['source'] = $source;
        $class = new $this->REMOTE_TYPE[$this->config['remote']]($this->uniacid, $this->config);
        return $class->upLoadRemoteByPath($param);
    }



    //上传本地图片(表单提交)
    public function uploadImages($parame)
    {
        $parame['flag'] = 1;
        if(empty($parame['file_type'])){
            $parame['file_type'] = 1;
        }
        $this->checkUpImagesExt($parame['files'], $parame['file_type']);
        $file_type = $parame['file_type'] == 1 ? 0 : 1;
        $class = new $this->REMOTE_TYPE[$this->config['remote']]($this->uniacid, $this->config, $file_type);
        $images = $class->upLoadImages($parame);
        if(count($images) > 0){
            foreach ($images as $k => $item){
                $images[$k]['url'] = remote($this->uniacid, $item['url'], 1);
            }
        }
        return $images;
    }

    //裁剪图片保存
    public function uploadCutImages($parame)
    {
        $this->checkUpImagesSize($parame['files']['files']->getInfo()['size']);
        $this->config['pic_id'] = 0;
        $this->config['type'] = 1;
        if(isset($parame['type']) && $parame['type'] == 2){ //覆盖
            $this->config['pic_id'] = $parame['pic_id'];
            $this->config['type'] = $parame['type'];
        }
        $class = new $this->REMOTE_TYPE[$this->config['remote']]($this->uniacid, $this->config);
        $images = $class->upLoadCutImg($parame);
        if(count($images) > 0){
            foreach ($images as $k => $item){
                $images[$k]['url'] = remote($this->uniacid, $item['url'], 1);
            }
        }
        return $images;
    }

    //检查图片上传格式
    public function checkUpImagesExt($files, $file_type=1)
    {
        $total_size = 0;
        $ext = $file_type == 1 ? $this->img_ext : $this->video_ext;
        // 默认限制：图片使用系统单文件限制，视频使用系统视频限制
        $file_size = $file_type == 1 ? $this->img_size : $this->video_size;
        // 会员级别的单文件大小限制（仅对图片生效）
        try{
            if($file_type == 1){
                $uid = request()->userID();
                if(!empty($uid)){
                    $this->syncUserEntitlementsQuietly($uid);
                    $user = WdXcxUser::where('id', $uid)->find();
                    if($user){
                        // TrueUploadSize 单位：当 type=1 时为 M；当 type=2 时为 G->转为 MB
                        // 这里统一按字节比较：值（MB） * 1024 * 1024
                        $vip_limit_mb = $user->TrueUploadSize;
                        if(!empty($vip_limit_mb) && $vip_limit_mb > 0){
                            $file_size = intval($vip_limit_mb) * 1024 * 1024;
                        }
                    }
                }
            }
        }catch (\Throwable $e){
            // 忽略会员限制读取异常，回退到系统默认限制
        }
        foreach ($files as $file){
            if($file->isValid()){
                if(!$this->isAllowedUploadFile($file, $ext, $file_type)){
                    throw new Exception('文件格式不正确');
                }
                if($file->getSize() > $file_size){
                    throw new Exception('文件超出限制');
                }
            }else{
                throw new Exception('文件无效');
            }
            $total_size = $total_size + $file->getSize();
        }
        $this->checkUpImagesSize($total_size);
    }

    private function isAllowedUploadFile($file, $allowedExts, $file_type)
    {
        $originalExt = strtolower((string)$file->getOriginalExtension());
        if ($originalExt !== '' && in_array($originalExt, $allowedExts)) {
            return true;
        }
        $info = method_exists($file, 'getInfo') ? $file->getInfo() : [];
        $nameExt = strtolower((string)pathinfo((string)($info['name'] ?? ''), PATHINFO_EXTENSION));
        if ($nameExt !== '' && in_array($nameExt, $allowedExts)) {
            return true;
        }
        $mime = $this->getMimeType($file);
        if ($file_type == 1 && strpos($mime, 'image/') === 0) {
            return true;
        }
        if ($file_type == 2 && strpos($mime, 'video/') === 0) {
            return true;
        }
        if ($file_type == 1 && $this->isReadableImage($file)) {
            return true;
        }
        return false;
    }

    private function getMimeType($file)
    {
        $path = method_exists($file, 'getRealPath') ? $file->getRealPath() : '';
        if (!$path || !is_file($path) || !function_exists('finfo_open')) {
            return '';
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            return '';
        }
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);
        return strtolower((string)$mime);
    }

    private function isReadableImage($file)
    {
        $path = method_exists($file, 'getRealPath') ? $file->getRealPath() : '';
        if (!$path || !is_file($path) || !function_exists('getimagesize')) {
            return false;
        }
        return @getimagesize($path) !== false;
    }



    //检查是否有上传图片的空间
    public function checkUpImagesSize($size)
    {
        $uid = request()->userID();
        $this->syncUserEntitlementsQuietly($uid);
        $user = WdXcxUser::where('id', $uid)->find();
        if(!$user){
            throw new Exception('用户不存在');
        }
        $limitSize = $user->UserCanUserPicSize;
        if($limitSize != -1){
            if($size > $limitSize*1024*1024){
                throw new Exception('用户可以使用上传空间不足');
            }
        }
    }

    private function syncUserEntitlementsQuietly($uid)
    {
        $uid = (int)$uid;
        if ($uid <= 0 || isset(self::$syncedEntitlementUsers[$uid])) {
            return;
        }
        self::$syncedEntitlementUsers[$uid] = true;
        (new JiafangyunEntitlementSyncService(app()))->syncUserQuietly($uid);
    }

    /**获取上传文件限制大小
     * @return float|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSingleFileSize()
    {
        $size = 524288000; // 500*1024*1024;
        $base = WdXcxBase::where('uniacid', $this->uniacid)
                    ->field('remote, use_remote, single_file_size')
                    ->find();
        if(!$base){
            $base = [
                'remote' => 1,
                'use_remote' => 1,
                'single_file_size' => 2,
            ];
        }
        if($base['use_remote'] == 2){ //单独设置
            if($base['remote'] == 1){
                $size = $base['single_file_size']*1024*1024;
            }
        }else{
            $size = $base['single_file_size']*1024*1024;
        }
        return $size;
    }



}
