<?php
namespace app\index\service\upload;
use app\index\model\WdXcxPic;
use OSS\Core\OssException;
use OSS\OssClient;
use think\Exception;

/**
 * Created by : PhpStorm
 * User: D0065
 * Date: 2022/1/15
 * Time: 14:22
 */

class AliOssService extends UploadFile
{
    const REMOTE_SOURCE = 'ali_oss';
    const DIRECTORY = [
        '0' => 'upimages',
        '1' => 'upvideos',
        '2' => 'upfiles',
        '3' => 'upaudios',
    ];
    protected $uniacid;
    protected $config;
    protected $aliAuth;

    protected $save_path;


    public function __construct($unaicid, $config, $type = 0)
    {
        $this->uniacid = $unaicid;
        $ali_config = $config['oss'];
        if(!$ali_config['bucket'] || !$ali_config['domain'] || !$ali_config['ak'] || !$ali_config['sk']){
            throw new Exception('阿里云OSS参数配置不完整');
        }
        $this->config = $ali_config;
        try{
            $this->aliAuth = self::aliOss($ali_config);
        }catch (OssException $exception){
            throw new Exception($exception->getMessage());
        }
        $this->save_path = self::DIRECTORY[$type] . '/'. $this->uniacid . '/' . date("Ymd", time()) . '/';
    }

    public function upLoadImages($parame)
    {
        $files = $parame['files'];
        $save_url = $key = $this->save_path;
        if($this->config['folder_name']){
            $key = $this->config['folder_name'] . '/' . $key;
        }
        $arrs = [];
        try{
            foreach ($files as $index => $item){
                $fileType = $parame['file_type'] ?? 1;
                $originalName = $this->getUploadNameForIndex($item, $fileType, $index, $parame['original_names'] ?? []);
                $ext = $this->getExt($originalName, $item, $fileType);
                $ext_name = md5(uniqid(microtime(true), true)) . '.' . $ext;
                $save_key = $key . $ext_name;
                $result = $this->aliAuth->uploadFile($this->config['bucket'], $save_key, $item->getRealPath());
                if($result){
                    $arrs[] = self::savePictureTo([
                        'gid' => $parame['gid'],
                        'pic_name' => $originalName,
                        'size' => $item->getSize(),
                        'url' => $save_url . $ext_name,
                        'flag' => $parame['flag'],
                        'remote_source' => self::REMOTE_SOURCE,
                        'shop_id' => $parame['shop_id'] ?? 0,
                        'uid' => $parame['uid'] ?? 0,
                        'file_type' => $fileType,
                    ]);
                }else{
                    throw new Exception('上传失败，请稍后重试');
                }
            }
        }catch (OssException $e){
            throw new Exception($e->getMessage());
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
            $key = $this->config['folder_name'] . '/' . $key;
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
        if($parame['type'] == 1){//直接保存
            $save_url = $key = $this->save_path;
            if($this->config['folder_name']){
                $key = $this->config['folder_name'] . '/' . $key;
            }
            $arrs = [];
            try{
                foreach ($files as $item){
                    $item_info = $item->getInfo();
                    $ext = $this->getExt($item_info['name']);
                    $ext_name = md5(uniqid(microtime(true), true)) . '.' . $ext;
                    $save_key = $key . $ext_name;
                    $result = $this->aliAuth->uploadFile($this->config['bucket'], $save_key, $item_info['tmp_name']);
                    if($result){
                        $arrs[] = self::savePictureTo([
                            'gid' => $original_pic['gid'],
                            'pic_name' => $this->getCutName($item_info['name']),
                            'size' => $item_info['size'],
                            'url' => $save_url . $ext_name,
                            'flag' => $parame['flag'],
                            'remote_source' => self::REMOTE_SOURCE,
                        ]);
                    }else{
                        throw new Exception('上传失败，请稍后重试');
                    }
                }
            }catch (OssException $e){
                throw new Exception($e->getMessage());
            }
            return $arrs;
        }else{
            foreach ($files as $item){
                $item_info = $item->getInfo();
                if($this->config['folder_name']){
                    $original_pic['imgurl'] = $this->config['folder_name'] . '/' . $original_pic['imgurl'];
                }
                $result = $this->aliAuth->uploadFile($this->config['bucket'], $original_pic['imgurl'], $item_info['tmp_name']);
                if($result){
                    file_get_contents(remote($this->uniacid, $original_pic['imgurl'], 1));
                }else{
                    throw new Exception('上传失败，请稍后重试');
                }
            }
            return [["url" => $original_pic['imgurl'], "pid" => $original_pic['id']]];
        }
    }

    public function upLoadFiles($parame)
    {
        // TODO: Implement upLoadFiles() method.
    }

    private function aliOss($aliConfig)
    {
        require_once root_path().'/vendor/aliyun/autoload.php';
        $accessKeyId = $aliConfig['ak'];
        $accessKeySecret = $aliConfig['sk'];
        $endpoint = $aliConfig['domain'];
        $bucket = $aliConfig['bucket'];
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        if (!$ossClient->doesBucketExist($bucket)) {  //bucket不存在则创建
            $ossClient->createBucket($bucket);
        }
        return $ossClient;
    }

    private function putFileString($source, $save_key)
    {
        try{
            $result = $this->aliAuth->putObject($this->config['bucket'], $save_key, $source);
            if(!$result){
                throw new Exception('保存失败，请稍后重试');
            }
        }catch (OssException $exception){
            throw new Exception($exception->getMessage());
        }
        return $result;
    }


}
