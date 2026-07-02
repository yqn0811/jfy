<?php
namespace app\index\service\upload;
use app\index\model\WdXcxPic;
use app\index\service\TencentCOSService;
use think\Exception;

/**
 * Created by : PhpStorm
 * User: D0065
 * Date: 2022/1/15
 * Time: 14:29
 */

class TenOssService extends UploadFile
{
    const REMOTE_SOURCE = 'ten_cos';
    const DIRECTORY = [
        '0' => 'upimages',
        '1' => 'upvideos',
        '2' => 'upfiles',
        '3' => 'upaudios',
    ];
    protected $uniacid;
    protected $config;

    public function upLoadFiles($parame)
    {
        // TODO: Implement upLoadCutImg() method.
    }

    protected $tenOssAuth;
    protected $save_path;

    public function __construct($uniacid, $config, $type = 0)
    {
        $this->uniacid = $uniacid;
        $ten_cos = $config['ten_cos'];
        if(!$ten_cos['bucket'] || !$ten_cos['region'] || !$ten_cos['ak'] || !$ten_cos['sk']){
            throw new Exception('腾讯云参数配置不完整');
        }
        $this->config = $ten_cos;
        $this->save_path = self::DIRECTORY[$type] . '/' . $this->uniacid . '/' . date("Ymd", time()) . '/';
    }

    public function upLoadImages($parame)
    {
//        vendor('qcloud.vendor.autoload');
        $files = $parame['files'];
        $arrs = [];
        try{
            foreach ($files as $item){
                $fileType = $parame['file_type'] ?? 1;
                $originalName = $this->getUploadName($item, $fileType);
                $ext = $this->getExt($originalName, $item, $fileType);
                $key1 = $this->save_path . md5(uniqid(microtime(true), true)) . '.' . $ext;
                $shortUrl = $key1;
                if($this->config['folder_name']){
                    $shortUrl = $this->config['folder_name'] . '/' . $key1;
                }
                $cos = new TencentCOSService($this->config, $shortUrl);
                $url = $cos->upload($item->getRealPath(), true);
                if ($url) {
                    $arrs[] = self::savePictureTo([
                        'gid' => $parame['gid'],
                        'pic_name' => $originalName,
                        'size' => $item->getSize(),
                        'url' => $key1,
                        'flag' => $parame['flag'],
                        'remote_source' => self::REMOTE_SOURCE,
                        'shop_id' => $parame['shop_id'] ?? 0,
                        'uid' => $parame['uid'] ?? 0,
                        'file_type' => $fileType,
                    ]);
                }else{
                    throw new Exception($cos->getErrorMessage());
                }
            }
        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }
        return $arrs;
    }

    public function upLoadRemote($parame)
    {
        $source = $parame['source'];
        $save_url = $key = $this->save_path;
        if($this->config['folder_name']){
            $key = $this->config['folder_name'] . '/' . $key;
        }
        $pic_name = $this->createPicName() . image_type_to_extension($parame['info'][2]);
        $save_key = $key . $pic_name;
        $this->putFileString($source, $save_key);
        $return_data = self::savePictureTo([
            'gid' => $parame['gid'],
            'pic_name' => $pic_name,
            'size' => strlen($source),
            'url' => $save_url . $pic_name,
            'flag' => $parame['flag'],
            'remote_source' => self::REMOTE_SOURCE,
        ]);
        return [$return_data];
    }

    public function upLoadRemoteByPath($param)
    {
        $key = $param['save_key'];
        if($this->config['folder_name']){
            $key = $this->config['folder_name'] . '/' .$key;
        }
        $this->putFileString($param['source'], $key);
        return $key;
    }

    public function upLoadCutImg($parame)
    {
        $original_pic = WdXcxPic::get($parame['pic_id']);
        if(!$original_pic){
            throw new Exception('裁剪原图片不存在');
        }
        $files = $parame['files'];
        vendor('qcloud.vendor.autoload');
        if($parame['type'] == 1) {//直接保存
            $arrs = [];
            try{
                foreach ($files as $item){
                    $item_info = $item->getInfo();
                    $ext = $this->getExt($item_info['name']);
                    $key1 = $this->save_path . md5(uniqid(microtime(true), true)) . '.' . $ext;
                    $shortUrl = $key1;
                    if($this->config['folder_name']){
                        $shortUrl = $this->config['folder_name'] . '/' . $key1;
                    }
                    $cos = new TencentCOSService($this->config, $shortUrl);
                    $url = $cos->upload($item_info['tmp_name'], true);
                    if ($url) {
                        $arrs[] = self::savePictureTo([
                            'gid' => $original_pic['gid'],
                            'pic_name' => $this->getCutName($item_info['name']),
                            'size' => $item_info['size'],
                            'url' => $key1,
                            'flag' => $parame['flag'],
                            'remote_source' => self::REMOTE_SOURCE,
                        ]);
                    }else{
                        throw new Exception($cos->getErrorMessage());
                    }
                }
            }catch (Exception $exception){
                throw new Exception($exception->getMessage());
            }
            return $arrs;
        }else{
            try{
                foreach ($files as $item){
                    $item_info = $item->getInfo();
                    $shortUrl = $original_pic['imgurl'];
                    if($this->config['folder_name']){
                        $shortUrl = $this->config['folder_name'] . '/' . $shortUrl;
                    }
                    $cos = new TencentCOSService($this->config, $shortUrl);
                    $url = $cos->upload($item_info['tmp_name'], true);
                    if ($url) {

                    }else{
                        throw new Exception($cos->getErrorMessage());
                    }
                }
            }catch (Exception $exception){
                throw new Exception($exception->getMessage());
            }
            return [["url" => $original_pic['imgurl'], "pid" => $original_pic['id']]];
        }
    }

    private function putFileString($source, $save_key)
    {
        vendor('qcloud.vendor.autoload');
        $cos = new TencentCOSService($this->config, $save_key);
        $url = $cos->uploadString($source, true);
        if(!$url){
            throw new Exception($cos->getErrorMessage());
        }
        if(strpos($url, 'http') === false){
            $url = 'http://'. $url;
        }
        return $url;
    }


}
