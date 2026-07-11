<?php
/**
 * Created by : PhpStorm
 * User: D0065
 * Date: 2021/8/30
 * Time: 15:17
 */

namespace app\common\service;


use app\common\model\WdXcxPic;
use app\common\service\TencentCOSService;
use think\Db;
use think\Exception;
use think\Log;

class RemoteObjectService extends BaseService
{
    public function deleteRemoteObject($uniacid, $ids)
    {
        $pics = WdXcxPic::onlyTrashed()
                    ->where('id', 'in', $ids)->select();
        $del_local_obj = [];
        $del_qiniu_obj = [];
        $del_oss_obj = [];
        $del_cos_obj = [];
        $pic_ids = [];
        foreach ($pics as $item){
            $pic_ids[] = $item->id;
            if($item->getData('type') == 1){
                $del_local_obj[$item->id] = $item->imgurl;
            }
            if($item->getData('type') == 2){
                $del_qiniu_obj[$item->id] = trim($item->imgurl, '/');
            }
            if($item->getData('type') == 3){
                $del_oss_obj[$item->id] = trim($item->imgurl, '/');
            }
            if($item->getData('type') == 5){
                if(strpos($item->imgurl, 'http') === false)
                $del_cos_obj[$item->id] = trim($item->imgurl, '/');
            }
        }
        $config = $this->getRemoteConfig($uniacid);
        try{

            if(count($del_local_obj) > 0){
                $this->doDelLocalObj($del_local_obj);
            }
            if(count($del_qiniu_obj) > 0){
                $this->doDelQiniuObj($del_qiniu_obj, $config['qiniu']);
            }
            if(count($del_oss_obj) > 0){
                $this->doDelOssObj($del_oss_obj, $config['aliOss']);
            }
            if(count($del_cos_obj) > 0){
                $this->doDelCosObj($del_cos_obj, $config['cos']);
            }

        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
            Log::info('删除失败：'.$exception->getMessage());
            WdXcxPic::destroy($pic_ids, true);
        }
    }

    /**根据路径删除源文件
     * @param $uniacid
     * @param $path_arr  aray 非全路径的数组  不带域名的
     * @param $type
     * @return void
     * @throws Exception
     */
    public function deleteRemoteObjectByPath($uniacid, $path_arr, $type='')
    {
        $config = $this->getRemoteConfig($uniacid);
        $remote = $type ? $type : $config['remote'];
        if($remote == 1){
            $this->doDelLocalObj($path_arr, false);
        }
        if($remote == 2){
            $this->doDelQiniuObj($path_arr, $config['qiniu'], false);
        }
        if($remote == 3){
            $this->doDelOssObj($path_arr, $config['aliOss'], false);
        }
        if($remote == 4){
            $this->doDelCosObj($path_arr, $config['cos'], false);
        }

    }

    //删除本地图片
    private function doDelLocalObj($del_obj, $deleteRecord = true)
    {
        foreach ($del_obj as $key => $item){
            $url = trim($item, '/');
            $link = ROOT_PATH . 'public/' . $url;
            if(file_exists($link)){
                unlink($link);
            }
            if($deleteRecord){
                WdXcxPic::destroy($key, true);
            }
        }
    }

    //删除七牛图片
    private function doDelQiniuObj($del_obj, $qinu, $deleteRecord = true)
    {
        vendor('Qiniu.autoload');
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = $qinu['ak'];
        $secretKey = $qinu['sk'];
        if(!$qinu['ak'] || !$qinu['sk'] || !$qinu['bucket']){
            throw new Exception('七牛存储参数不完整');
        }
        // 构建鉴权对象
        $auth = new \Qiniu\Auth($accessKey, $secretKey);
        // 要上传的空间
        $bucket = $qinu['bucket'];
        $config = new \Qiniu\Config();
        $buckerManager = new \Qiniu\Storage\BucketManager($auth, $config);
        $del_arr = [];
        $del_key = [];
        foreach ($del_obj as $key => $item){
            $item = $qinu['folder_name'] ? $qinu['folder_name'] . '/' .$item : $item;
            array_push($del_arr, $item);
            array_push($del_key, $key);
        }
        $ops = $buckerManager->buildBatchDelete($bucket, $del_arr);
        list($ret, $err) = $buckerManager->batch($ops);
        if($err){
            throw new Exception($err);
        }
        if($deleteRecord){
            WdXcxPic::destroy($del_key, true);
        }
    }

    //删除OSS图片
    private function doDelOssObj($del_obj, $ali_info, $deleteRecord = true)
    {
        require_once root_path().'/vendor/aliyun/autoload.php';
        $accessKeyId = $ali_info['ak'];
        $accessKeySecret = $ali_info['sk'];
        $endpoint = $ali_info['domain'];
        $bucket = $ali_info['bucket'];
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $del_key = [];
            foreach ($del_obj as $key => $item){
                $item = $ali_info['folder_name'] ? $ali_info['folder_name'] . '/' .$item : $item;
                $ossClient->deleteObject($bucket, $item);
                $del_key[] = $key;
            }
            if($deleteRecord && !empty($del_key)){
                WdXcxPic::destroy($del_key, true);
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    //删除腾讯云图片
    private function doDelCosObj($del_obj, $cos_info, $deleteRecord = true)
    {
        try{
            foreach ($del_obj as $key => $item){
                if($cos_info['folder_name']){
                    if(strpos($item, $cos_info['folder_name']) === false){
                        $item = $cos_info['folder_name'] . '/' .$item;
                    }
                }
                $cos = new TencentCOSService($cos_info, $item);
                $cos->delObject();
                if($deleteRecord){
                    WdXcxPic::destroy($key, true);
                }
            }
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }

    }

    public function getRemoteConfig($uniacid)
    {
        return cacheRemoteSet($uniacid);
    }
}
