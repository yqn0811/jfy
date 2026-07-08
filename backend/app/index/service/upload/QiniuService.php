<?php
namespace app\index\service\upload;
use app\index\model\WdXcxPic;
use Qiniu\Cdn\CdnManager;
use think\Exception;

/**
 * Created by : PhpStorm
 * User: D0065
 * Date: 2022/1/15
 * Time: 14:22
 */

class QiniuService extends UploadFile
{
    const REMOTE_SOURCE = 'qiniu';
    const DIRECTORY = [
        '0' => 'upimages',
        '1' => 'upvideos',
        '2' => 'upfiles',
        '3' => 'upaudios',
    ];
    protected $uniacid;
    protected $config;
    protected $qiniuAuth;
    protected $save_path;

    public function __construct($unaicid, $config, $type = 0)
    {
        $this->uniacid = $unaicid;
        $qiniu_config = $config['qiniu'];
        if(!$qiniu_config['bucket'] || !$qiniu_config['domain'] || !$qiniu_config['ak'] || !$qiniu_config['sk']){
            throw new Exception('七牛参数配置不完整');
        }
        $this->config = $qiniu_config;
        $config['type'] = isset($config['type']) ? $config['type'] : 1;
        $config['pic_id'] = isset($config['pic_id']) ? $config['pic_id'] : 1;
        $this->qiniuAuth = self::qiniuAuth($qiniu_config, $config['type'], $config['pic_id']);
        $this->save_path = self::DIRECTORY[$type] . '/'. $this->uniacid . '/' . date("Ymd", time()) . '/';
    }

    public function upLoadImages($parame)
    {
        $files = $parame['files'];
        $key = $this->save_path;
        if($this->config['folder_name']){
            $key = $this->config['folder_name'] . '/' . $key;
        }
        $arrs = [];
        foreach ($files as $index => $item){
            $item_info = $item->getInfo();
            $fileType = $parame['file_type'] ?? 1;
            $originalName = $this->getUploadNameForIndex($item, $fileType, $index, $parame['original_names'] ?? []);
            $ext = $this->getExt($originalName, $item, $fileType);
            $save_key = $key . md5(uniqid(microtime(true), true)) . '.' . $ext;
            list($ret, $err) = $this->qiniuAuth['uploadMgr']->putFile($this->qiniuAuth['token'], $save_key, $item_info['tmp_name']);
            if($err !== null){
                throw new Exception('七牛参数异常，上传失败');
            }
            $url = $ret['key'];
            if($this->config['folder_name']){
                $url = str_replace($this->config['folder_name'].'/', '', $ret['key']);
            }
            $arrs[] = self::savePictureTo([
                'gid' => $parame['gid'],
                'pic_name' => $originalName,
                'size' => $item_info['size'],
                'url' => $url,
                'flag' => $parame['flag'],
                'remote_source' => self::REMOTE_SOURCE,
                'shop_id' => $parame['shop_id'] ?? 0,
                'uid' => $parame['uid'] ?? 0,
                'file_type' => $fileType,
            ]);
        }
        return $arrs;
    }

    public function upLoadRemote($parame)
    {
        $source = $parame['source'];
        $key = $this->save_path;
        if($this->config['folder_name']){
            $key = $this->config['folder_name'] . '/' . $key;
        }
        $pic_name = $this->createPicName() . image_type_to_extension($parame['info'][2]);
        $save_key = $key . $pic_name;
        $url = $this->putFileString($source, $save_key);
        $return_data = self::savePictureTo([
            'gid' => $parame['gid'],
            'pic_name' => $pic_name,
            'size' => strlen($source),
            'url' => $url,
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
        return $this->putFileString($param['source'], $key);
    }

    public function upLoadCutImg($parame)
    {
        $original_pic = WdXcxPic::get($parame['pic_id']);
        if(!$original_pic){
            throw new Exception('裁剪原图片不存在');
        }
        $files = $parame['files'];
        if($parame['type'] == 1){//直接保存
            $key = $this->save_path;
            if($this->config['folder_name']){
                $key = $this->config['folder_name'] . '/' . $key;
            }
            $arrs = [];
            foreach ($files as $item){
                $item_info = $item->getInfo();
                $ext = $this->getExt($item_info['name']);
                $save_key = $key . md5(uniqid(microtime(true), true)) . '.' . $ext;
                list($ret, $err) = $this->qiniuAuth['uploadMgr']->putFile($this->qiniuAuth['token'], $save_key, $item_info['tmp_name']);
                if($err !== null){
                    throw new Exception('七牛参数异常，上传失败');
                }
                $url = $ret['key'];
                if($this->config['folder_name']){
                    $url = str_replace($this->config['folder_name'].'/', '', $ret['key']);
                }
                $arrs[] = self::savePictureTo([
                    'gid' => $original_pic['gid'],
                    'pic_name' => $this->getCutName($item_info['name']),
                    'size' => $item_info['size'],
                    'url' => $url,
                    'flag' => $parame['flag'],
                    'remote_source' => self::REMOTE_SOURCE,
                ]);
            }
            return $arrs;
        }else{ //覆盖
            foreach ($files as $item){
                if($this->config['folder_name']){
                    $original_pic['imgurl'] = $this->config['folder_name'] . '/' . $original_pic['imgurl'];
                }
                $item_info = $item->getInfo();
                list($ret, $err) = $this->qiniuAuth['uploadMgr']->putFile($this->qiniuAuth['token'], $original_pic['imgurl'], $item_info['tmp_name']);
                if($err !== null){
                    throw new Exception('七牛参数异常，上传失败');
                }
                //刷新cdn
                $this->refreshUrl([remote($this->uniacid, $original_pic['imgurl'], 1)]);
            }
            return [["url" => $original_pic['imgurl'], "pid" => $original_pic['id']]];
        }

    }

    public function upLoadFiles($parame)
    {
        // TODO: Implement upLoadFiles() method.
    }

    private function qiniuAuth($qiniuConfig, $type, $picId)
    {
        vendor('Qiniu.autoload');
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = $qiniuConfig['ak'];
        $secretKey = $qiniuConfig['sk'];
        // 构建鉴权对象
        $auth = new \Qiniu\Auth($accessKey, $secretKey);
        // 要上传的空间
        $bucket = $qiniuConfig['bucket'];
        if($type == 2){ //覆盖传值凭证
            $original_pic = WdXcxPic::get($picId);
            if(!$original_pic){
                throw new Exception('原图片不存在');
            }
            $keyToOverwrite = $original_pic['imgurl'];
            if($this->config['folder_name']){
                $keyToOverwrite = $this->config['folder_name'] . '/' . $keyToOverwrite;
            }
            $token = $auth->uploadToken($bucket, $keyToOverwrite);
        }else{
            $token = $auth->uploadToken($bucket);
        }

        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new \Qiniu\Storage\UploadManager();
        return [
            'token' => $token,
            'uploadMgr' => $uploadMgr,
        ];
    }

    //刷新新缓存
    private function refreshUrl($urls)
    {
        vendor('Qiniu.autoload');
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = $this->config['ak'];
        $secretKey = $this->config['sk'];
        // 构建鉴权对象
        $auth = new \Qiniu\Auth($accessKey, $secretKey);
        $cdnMgr = new CdnManager($auth);
        list($refreshResult, $refreshErr) = $cdnMgr->refreshUrls($urls);
        if($refreshErr !== null){
            throw new Exception('刷新失败，请到七牛后台手动刷新');
        }
    }

    private function putFileString($source, $save_key)
    {
        list($ret, $err) = $this->qiniuAuth['uploadMgr']->put($this->qiniuAuth['token'], $save_key, $source);
        if($err !== null){
            throw new Exception('七牛参数异常，上传失败');
        }
        $url = $ret['key'];
        if($this->config['folder_name']){
            $url = str_replace($this->config['folder_name'].'/', '', $ret['key']);
        }
        return $url;

    }



}
