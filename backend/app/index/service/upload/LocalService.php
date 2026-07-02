<?php
namespace app\index\service\upload;

use app\index\model\WdXcxPic;
use think\Exception;
use think\facade\Filesystem;
use think\File;

/**
 * Created by : PhpStorm
 * User: D0065
 * Date: 2022/1/15
 * Time: 14:27
 */

class LocalService extends UploadFile
{
    const REMOTE_SOURCE = 'loacl';
    const DIRECTORY = [
        '0' => 'upimages',
        '1' => 'upvideos',
        '2' => 'upfiles',
        '3' => 'upaudios',
    ];
    const FILE_EXT = [
        '0' => 'jpg,png,gif,jpeg',
        '1' => 'avi,wmv,mpeg,mp4,m4v,mov,asf,flv,f4v,rmvb,rm,3gp,vob',
        '2' => 'txt,pdf,PDF,doc,docx,xlsx,xls,zip,rar,exe,gz,ppt,pptx',
        '3' => 'cd,wave,aiff,mpeg,mp3,mpeg-4,midi,wma,RealAudio,vqf,ogg,amr,ape,flac,aac',
    ];
    protected $uniacid;
    protected $static_root = '';
    protected $config;
    protected $move_path; //项目目录/public/upimages/162
    protected $save_url;  //upimages/162/20220101/
    protected $type; //0图片，1视频，2文件,3音频

    public function __construct($unaicid, $config, $type = 0)
    {
        $this->uniacid = $unaicid;
        $this->config = $config;
        $this->move_path = self::DIRECTORY[$type] . '/' . $unaicid . '/' . date('Ymd');
        $this->save_url = $this->static_root. "/" . self::DIRECTORY[$type] . "/" . $this->uniacid . '/' . date("Ymd", time()) . "/";
        $this->type = $type;
    }

    public function upLoadImages($parame)
    {
        $files = $parame['files'];
        $arrs = [];
        foreach($files as $item){
            $fileType = $parame['file_type'] ?? 1;
            $originalName = $this->getUploadName($item, $fileType);
            // 移动到框架应用根目录/public/upimages/ 目录下
            $file_name = Filesystem::disk('public')->putFileAs($this->move_path, $item, $originalName);
            if ($file_name) {
                $url = $this->save_url . $originalName;
                if($this->type == 0 && !$this->check_illegal($url)){
                    throw new Exception('异常图像文件');
                }
                $arrs[] = self::savePictureTo([
                    'gid' => $parame['gid'],
                    'pic_name' => $originalName,
                    'size' => $item->getSize(),
                    'url' => $url,
                    'flag' => $parame['flag'],
                    'remote_source' => self::REMOTE_SOURCE,
                    'shop_id' => $parame['shop_id'] ?? 0,
                    'uid' => $parame['uid'] ?? 0,
                    'file_type' => $fileType,
                ]);
            } else {
                // 上传失败获取错误信息
                throw new Exception($item->getError());
            }
        }
        return $arrs;
    }

    public function upLoadRemote($parame)
    {
        $save_path = $this->move_path . '/'. date('Ymd');
        $this->createDir($save_path);
        $pic_name = $this->createPicName().image_type_to_extension($parame['info'][2]);
        $result = file_put_contents($save_path . '/'.$pic_name, $parame['source']);
        $url = $this->save_url  . $pic_name;
        if(!$this->check_illegal($save_path . '/'.$pic_name)){
            throw new Exception('异常图像文件');
        }
        if($result){
            $return_data = $this->savePictureTo([
                'gid' => $parame['gid'],
                'pic_name' => $pic_name,
                'size' => $result,
                'url' => $url,
                'flag' => $parame['flag'],
                'remote_source' => self::REMOTE_SOURCE,
            ]);
            return [$return_data];
        }else{
            throw new Exception('保存远程图片失败');
        }
    }

    public function upLoadCutImg($parame)
    {
        $files = $parame['files'];
        $arrs = [];
        $original_pic = WdXcxPic::get($parame['pic_id']);
        if(!$original_pic){
            throw new Exception('裁剪原图片不存在');
        }
        foreach($files as $item){
            // 移动到框架应用根目录/public/upimages/ 目录下
            if($parame['type'] == 1){ //直接保存
                $info = $item->validate(['ext' => 'jpg,png,gif,jpeg'])->move($this->move_path);
                if ($info) {
                    $url = $this->save_url . $info->getFilename();
                    if(!$this->check_illegal($url)){
                        throw new Exception('异常图像文件');
                    }
                    $arrs[] = self::savePictureTo([
                        'gid' => $original_pic['gid'],
                        'pic_name' => $this->getCutName($info->getInfo()['name']),
                        'size' => $info->getInfo()['size'],
                        'url' => $url,
                        'flag' => $parame['flag'],
                        'remote_source' => self::REMOTE_SOURCE,
                    ]);
                } else {
                    // 上传失败获取错误信息
                    throw new Exception($item->getError());
                }
            }else{ //覆盖保存
                $index = strripos($original_pic['imgurl'], '/');
                $move_path = substr($original_pic['imgurl'], 0, $index+1);
                $save_name = substr($original_pic['imgurl'], $index+1, strlen($original_pic['imgurl']));
                $info = $item->validate(['ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH. 'public' .$move_path, $save_name, true);
                if($info){

                }else{
                    // 上传失败获取错误信息
                    throw new Exception($item->getError());
                }
            }
        }
        return $arrs;
    }

    public function upLoadFiles($parame)
    {
        // TODO: Implement upLoadFiles() method.
    }

    public function check_illegal($image)
    {
        if (file_exists($image)) {
            $resource = fopen($image, 'rb');
            $fileSize = filesize($image);
            fseek($resource, 0);
            if ($fileSize > 512) { // 取头和尾
                $hexCode = bin2hex(fread($resource, 512));
                fseek($resource, $fileSize - 512);
                $hexCode .= bin2hex(fread($resource, 512));
            } else { // 取全部
                $hexCode = bin2hex(fread($resource, $fileSize));
            }
            fclose($resource);
            /* 匹配16进制中的 <% ( ) %> */
            /* 匹配16进制中的 <? ( ) ?> */
            /* 匹配16进制中的 <script | /script> 大小写亦可*/
            if (preg_match("/(3c25)|(3c3f.*?706870)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is", $hexCode)) {
                return 'false';
            }
        }
        return 'true';
    }

    public function upLoadRemoteByPath($param)
    {
        // TODO: Implement upLoadRemoteByPath() method.
    }
}
