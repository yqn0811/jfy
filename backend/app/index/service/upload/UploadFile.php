<?php
namespace app\index\service\upload;

use app\index\model\WdXcxPic;

/**
 * Created by : PhpStorm
 * User: D0065
 * Date: 2022/1/15
 * Time: 14:33
 */

abstract class UploadFile
{
    const REMOTE_TYPE = [
        'loacl' => 1,
        'qiniu' => 2,
        'ali_oss' => 3,
        'ten_cos' => 4,
    ];

    /**上传图片
     * @return mixed
     */
    abstract public function upLoadImages($parame);

    /**上传远程图片
     * @return mixed
     */
    abstract public function upLoadRemote($parame);

    /**上传图片以为的文件
     * @return mixed
     */
    abstract public function upLoadFiles($parame);

    /**保存裁剪的图片
     * @param $parame
     * @return mixed
     */
    abstract public function upLoadCutImg($parame);

    /**上传服务器内的文件
     * @param $param(文件资源，文件名称)
     * @return mixed
     */
    abstract public function upLoadRemoteByPath($param);

    /**获取上传图片的扩展名
     * @param $name
     * @return mixed
     */
    protected function getExt($name, $file = null, $fileType = 1)
    {
        $ext = strtolower((string)pathinfo((string)$name, PATHINFO_EXTENSION));
        if ($ext !== '') {
            return $ext;
        }
        if ($file && method_exists($file, 'getRealPath')) {
            $mime = $this->getMimeType($file->getRealPath());
            $mimeExt = $this->getExtFromMime($mime, $fileType);
            if ($mimeExt !== '') {
                return $mimeExt;
            }
        }
        return $fileType == 2 ? 'mp4' : 'jpg';
    }

    protected function getUploadName($file, $fileType = 1)
    {
        $name = '';
        if (method_exists($file, 'getOriginalName')) {
            $name = (string)$file->getOriginalName();
        }
        if ($name === '' && method_exists($file, 'getInfo')) {
            $info = $file->getInfo();
            $name = (string)($info['name'] ?? '');
        }
        $ext = $this->getExt($name, $file, $fileType);
        if ($name === '' || strtolower((string)pathinfo($name, PATHINFO_EXTENSION)) === '') {
            return 'upload_' . date('YmdHis') . mt_rand(1000, 9999) . '.' . $ext;
        }
        return $name;
    }

    protected function getUploadNameForIndex($file, $fileType = 1, $index = null, $originalNames = [])
    {
        $clientName = '';
        if (is_array($originalNames)) {
            if ($index !== null && isset($originalNames[$index])) {
                $clientName = (string)$originalNames[$index];
            } elseif (isset($originalNames['default'])) {
                $clientName = (string)$originalNames['default'];
            }
        }
        if ($clientName !== '') {
            $safeName = $this->normalizeClientUploadName($clientName, $file, $fileType);
            if ($safeName !== '') {
                return $safeName;
            }
        }
        return $this->getUploadName($file, $fileType);
    }

    protected function normalizeClientUploadName($name, $file, $fileType = 1)
    {
        $name = urldecode((string)$name);
        $name = str_replace(["\\", "/"], '_', $name);
        $name = preg_replace('/[\\x00-\\x1F:*?"<>|]/u', '_', $name);
        $name = trim($name);
        $name = trim($name, '.');
        if ($name === '') {
            return '';
        }
        if (function_exists('mb_strlen') && mb_strlen($name, 'UTF-8') > 120) {
            $name = mb_substr($name, 0, 120, 'UTF-8');
        } elseif (strlen($name) > 180) {
            $name = substr($name, 0, 180);
        }
        $expectedExt = $this->getExt($name, $file, $fileType);
        $nameExt = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
        if ($nameExt === '') {
            return $name . '.' . $expectedExt;
        }
        if (!$this->isAllowedClientUploadExt($nameExt, $fileType)) {
            return '';
        }
        return $name;
    }

    protected function isAllowedClientUploadExt($ext, $fileType = 1)
    {
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'];
        $videoExts = ['avi', 'wmv', 'mpeg', 'mp4', 'm4v', 'mov', 'asf', 'flv', 'f4v', 'rmvb', 'rm', '3gp', 'vob'];
        return in_array(strtolower((string)$ext), $fileType == 1 ? $imageExts : $videoExts, true);
    }

    protected function getMimeType($path)
    {
        if (!$path || !is_file($path)) {
            return '';
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                return strtolower((string)$mime);
            }
        }
        return '';
    }

    protected function getExtFromMime($mime, $fileType = 1)
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'jpg',
            'image/heic' => 'jpg',
            'image/heif' => 'jpg',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/3gpp' => '3gp',
        ];
        if (isset($map[$mime])) {
            return $map[$mime];
        }
        if ($fileType == 1 && strpos($mime, 'image/') === 0) {
            return 'jpg';
        }
        if ($fileType == 2 && strpos($mime, 'video/') === 0) {
            return 'mp4';
        }
        return '';
    }

    /**创建多级目录
     * @param $path
     */
    protected function createDir($path)
    {
        if(!is_dir($path)){
            mkdir($path, 0755, true);
        }
    }

    /**
     * 生成随机文件名
     */
    protected function createPicName()
    {
        return 'default_name' . time() . mt_rand(10000, 99999);
    }

    /**
     * 裁剪后直接保存的新名字
     */
    protected function getCutName($oriName)
    {
        return str_replace('.'.$this->getExt($oriName), '_cj'.mt_rand(100, 999).'.'.$this->getExt($oriName), $oriName);
    }

    /**
     * 图片保存到数据库
     */
    protected function savePictureTo($data)
    {
        if($data['flag'] == 1){
            $picture = WdXcxPic::savePicture([
                'uniacid' => $this->uniacid,
                'gid' => $data['gid'],
                'pic_name' => $data['pic_name'],
                'size' => $data['size'],
                'create_time' => time(),
                'imgurl' => $data['url'],
                'type' => self::REMOTE_TYPE[$data['remote_source']] == 4 ? 5 : self::REMOTE_TYPE[$data['remote_source']],
                'shop_id' => $data['shop_id'] ?? 0,
                'uid' => $data['uid'] ?? 0,
                'file_type' => $data['file_type'] ?? 0,
            ]);
            return ["url" => $data['url'], "pid" => $picture->id];
        }else{
            return ["url" => $data['url']];
        }
    }
}
