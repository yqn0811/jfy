<?php


namespace app\common\service;

use Qcloud\Cos\Client;
use think\Db;

class TencentCOSService
{
    protected static $cosClient;
    protected $savePath;//云端保存路径,包含文件名、扩展名
    protected $bucket;//格式：BucketName-APPID

    protected $errorCode;
    protected $errorMessage;

    public function __construct($tencent_cos, $savePath = 'image/')
    {
        $this->savePath = $savePath;
        $this->bucket = $tencent_cos['bucket'];

        self::$cosClient = new Client(
            [
                'region' => $tencent_cos['region'],//设置一个默认的存储桶地域
                'credentials' => [
                    'secretId' => $tencent_cos['sk'],//"云 API 密钥 SecretId"
                    'secretKey' => $tencent_cos['ak']//"云 API 密钥 SecretKey"
                ]
            ]
        );
    }

    /**
     * 上传
     * @param $file 本地文件
     * @param bool $backWholeUrl 是否返回完整url
     * @return false|\Guzzle\Http\Url|string
     */
    public function upload($file, $backWholeUrl = false)
    {
        try {
            $result = self::$cosClient->putObject([
                'Bucket' => $this->bucket,
                'Key' => $this->savePath,
                'Body' => fopen($file, 'rb')
            ]);
        } catch (\Exception $e) {
            $this->errorCode = 12;
            $this->errorMessage = $e->getMessage();
            return false;
        }
        return $backWholeUrl === false ?
            trim(parse_url(urldecode(((array)$result)["\0*\0data"]['Key']))['path'], '/') :
            urldecode(((array)$result)["\0*\0data"]['Location']);
    }

    /**
     * 上传字符
     * @param $file 本地文件
     * @param bool $backWholeUrl 是否返回完整url
     * @return false|\Guzzle\Http\Url|string
     */
    public function uploadString($source, $backWholeUrl = false)
    {
        try {
            $result = self::$cosClient->putObject([
                'Bucket' => $this->bucket,
                'Key' => $this->savePath,
                'Body' => $source
            ]);
        } catch (\Exception $e) {
            $this->errorCode = 12;
            $this->errorMessage = $e->getMessage();
            return false;
        }
        return $backWholeUrl === false ?
            trim(parse_url(urldecode(((array)$result)["\0*\0data"]['Key']))['path'], '/') :
            urldecode(((array)$result)["\0*\0data"]['Location']);
    }

    /**
     * @return \Guzzle\Http\Url|string
     */
    protected function getObjectUal()
    {
        return self::$cosClient->getObjectUrl($this->bucket, $this->savePath);
    }

    /**
     * 获取短时访问地址，用于私有读对象的受控原图下载。
     * @param string $expires 例如 +10 minutes
     * @return string
     */
    public function getSignedObjectUrl($expires = '+10 minutes')
    {
        try {
            return (string)self::$cosClient->getObjectUrl($this->bucket, $this->savePath, $expires);
        } catch (\Throwable $e) {
            $this->errorCode = 12;
            $this->errorMessage = $e->getMessage();
            return '';
        }
    }

    /**
     * 下载
     * @param $pathTo 本地保存路径
     * @return bool
     */
    public function download($pathTo)
    {
        try {
            self::$cosClient->getObject([
                'Bucket' => $this->bucket,
                'Key' => $this->savePath,
                'SaveAs' => $pathTo
            ]);
        } catch (\Exception $e) {
            $this->errorCode = 12;
            $this->errorMessage = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * 删除单个对象
     * @return bool
     */
    public function delObject()
    {
        try {
            self::$cosClient->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $this->savePath
            ]);
        } catch (\Exception $e) {
            $this->errorCode = 12;
            $this->errorMessage = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * 删除多个对象
     * @param array $savePaths 云端路径集合
     * @return bool
     */
    public function delObjects(array $objectPaths)
    {
        try {
            self::$cosClient->deleteObjects([
                'Bucket' => $this->bucket,
                'Objects' => array_map(function ($item) {
                    return ['Key' => $item];
                }, $objectPaths)
            ]);
        } catch (\Exception $e) {
            $this->errorCode = 12;
            $this->errorMessage = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * 获取对象列表
     * @param int $limit
     * @return array|false
     */
    public function getFileList($limit = 100, $marker)
    {
        try {
            $result = self::$cosClient->listObjects([
                'Bucket' => $this->bucket,
                'Marker' => $marker,
                'MaxKeys' => $limit
            ]);
        } catch (\Exception $e) {
            $this->errorCode = 12;
            $this->errorMessage = $e->getMessage();
            return false;
        }
        return array_column(((array)$result)["\0*\0data"]['Contents'], 'Key');
    }

    /**
     * 对象是否存在
     * @return bool
     */
    public function exists()
    {
        try {
            self::$cosClient->headObject(array(
                'Bucket' => $this->bucket,
                'Key' => $this->savePath,
            ));
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
