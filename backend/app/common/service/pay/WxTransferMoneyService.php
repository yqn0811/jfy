<?php

namespace app\common\service\pay;

use think\cache\driver\Redis;
use think\facade\Config;
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;

/**
 * @author
 * 微信支付V3版本，目前仅供商家转账使用
 * Class PayV3
 * @package common\util
 * @property-read string transfer 微信打款
 */
class WxTransferMoneyService
{
    protected $certificatesUrl = 'https://api.mch.weixin.qq.com/v3/certificates';
    protected $api = [
        'transfer' => 'v3/transfer/batches'
    ];

    protected $uniacid;
    protected $params;
    protected $applet;

    protected $errorMessage;

    public function setParams($params)
    {
        $this->uniacid = 1;
        $this->params = $params;
        return $this;
    }

    public function __get($name)
    {
        if (!self::certificate()) {
            return false;
        }
        return $this->{$name}();
    }

    /**
     * 商家批量发起转账
     * 为兼容原功能仅提供单笔转账
     */
    public function transfer()
    {
        $uniacid = $this->uniacid;
        $params = $this->params;
        // 设置参数
        // 商户号
        $merchantId = Config::get('miniprogram.mchid');
        // 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
        $merchantPrivateKeyFilePath = $this->fileUri($this->merchantPrivateKeyPath($uniacid));
        $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);
        // 「商户API证书」的「证书序列号」
        $merchantCertificateSerial = Config::get('miniprogram.api_serial_sn');
        // 从本地文件中加载「微信支付平台证书」，用来验证微信支付应答的签名
        $platformCertificateFilePath = $this->fileUri($this->platformCertificatePath($merchantId));
        $platformPublicKeyInstance = Rsa::from($platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);
        // 从「微信支付平台证书」中获取「证书序列号」
        $platformCertificateSerial = PemUtil::parseCertificateSerialNo($platformCertificateFilePath);
        $instance = Builder::factory([
            'mchid' => $merchantId,
            'serial' => $merchantCertificateSerial,
            'privateKey' => $merchantPrivateKeyInstance,
            'certs' => [
                $platformCertificateSerial => $platformPublicKeyInstance,
            ],
        ]);
        $out_batch_no = $this->params['order_sn'];
        $out_detail_no = $this->params['order_sn'] . rand(00,99);
        // 发送请求
        $data = [
            'json' => [
                "appid" => Config::get('miniprogram.appid'),
                "out_batch_no" => $out_batch_no,
                "batch_name" => $params['title'],
                "batch_remark" => $params['remark'],
                "total_amount" => (int)bcmul($params['money'], 100),
                "total_num" => 1,
                "transfer_detail_list" => [
                    [
                        "out_detail_no" => $out_detail_no,
                        "transfer_amount" => (int)bcmul($params['money'], 100),
                        "transfer_remark" => $params['remark'],
                        "openid" => $params['openid'],
                        'user_name' => $this->sensitiveEncryption($params['user_name'], $platformCertificateFilePath)
                    ]
                ]
            ],
            'headers' => [
                'Wechatpay-Serial' => $platformCertificateSerial
            ]
        ];
        try {
            $instance->chain($this->api['transfer'])->post($data);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $temp_msg = explode('response:', $msg);
            if(!empty($temp_msg[1])){
                $temp_msg = json_decode($temp_msg[1], true);
                if($temp_msg && !empty($temp_msg['message'])){
                    $msg = $temp_msg['message'];
                }
            }
            $this->errorMessage = $msg;
            return false;
        }
        return true;
    }

    /**
     * 证书生成
     * @return bool
     */
    protected function certificate()
    {
        //平台证书
        if (!$this->certificateCheckAndDownload($this->uniacid, Config::get('miniprogram.mchid'), Config::get('miniprogram.api_serial_sn'), Config::get('miniprogram.signkey_v3'))) {
            return false;
        }
        return true;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }


    /**
     * 敏感信息加密
     * @param $str
     * @return string
     * @throws \Exception
     */
    public function sensitiveEncryption($str, $publicKeyPath)
    {
        //$str是待加密字符串
        $public_key = file_get_contents($publicKeyPath);
        $encrypted = '';
        if (openssl_public_encrypt($str, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING)) {
            //base64编码
            $sign = base64_encode($encrypted);
        } else {
            throw new \Exception('encrypt failed');
        }
        return $sign;
    }

    /**
     * @param $uniacid
     * @param $merchantId 商户号
     * @param $serialNo API证书序列号
     * @param $v3key 支付APIv3密钥
     * @throws \yii\httpclient\Exception
     */
    public function certificateCheckAndDownload($uniacid, $merchantId, $serialNo, $v3key)
    {
        $key_path = $this->merchantPrivateKeyPath($uniacid);
        $path = __DIR__ . "/PlatformCert";

        //是否需要执行
        $file = $path . '/' . $merchantId;
        $redis = new Redis(GetRedisConf());
        $time = $redis->get('time_platform_key_' . $merchantId);
        if (file_exists($file) && $time && time() < $time + 11 * 3600) {
            return true;
        }
        //平台证书密文
        $timestamp = time();
        $http_method = 'GET';
        $nonce = $timestamp . rand(10000, 99999);
        $body = '';

        $mch_private_key = openssl_get_privatekey(file_get_contents($key_path));//获取私钥
        $url_parts = parse_url($this->certificatesUrl);

        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $message = $http_method . "\n" .
            $canonical_url . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";
        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');
        $sign = base64_encode($raw_sign);
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $merchantId, $nonce, $timestamp, $serialNo, $sign);

        $result = $this->getrequestNew($this->certificatesUrl, [
            'User-Agent: https://zh.wikipedia.org/wiki/User_agent',
            'Accept: application/json',
            'Authorization: WECHATPAY2-SHA256-RSA2048 ' . $token
        ]);
        if (!empty($result['code']) && $result['message']) {
            $this->errorMessage = $result['message'];
            return false;
        }
        $result = $result['data'];
        array_multisort(array_column($result, 'effective_time'), SORT_DESC, $result);
        $result = $result[0];

        //解密
        $AesUtil = new AesUtil($v3key);
        $associatedData = $result['encrypt_certificate']['associated_data'];
        $nonceStr = $result['encrypt_certificate']['nonce'];
        $ciphertext = $result['encrypt_certificate']['ciphertext'];
        $certificate = $AesUtil->decryptToString($associatedData, $nonceStr, $ciphertext);

        //写入
        self::filePut($path, $merchantId, $certificate);

        //写入时间
        $redis->set('time_platform_key_' . $merchantId, time());
        return true;
    }

    public function filePut($basePath, $merchantId, $certificate)
    {
        if (!file_exists($basePath)) {
            if (mkdir($basePath, 0777, true)) {
                $upath = $basePath . '/' . $merchantId . "/";
                if (!file_exists($upath)) {
                    mkdir($upath, 0777, true);
                }
            }
        } else {
            $upath = $basePath . '/' . $merchantId . "/";
            if (!file_exists($upath)) {
                mkdir($upath, 0777, true);
            }
        }
        file_put_contents($upath . 'cert.pem', $certificate);
    }

    protected function merchantPrivateKeyPath($uniacid)
    {
        $configuredPath = (string)Config::get('miniprogram.api_private_key_path');
        return $configuredPath !== '' ? $configuredPath : __DIR__ . '/' . $uniacid . '/apiclient_key.pem';
    }

    protected function platformCertificatePath($merchantId)
    {
        $configuredPath = (string)Config::get('miniprogram.platform_cert_path');
        return $configuredPath !== '' ? $configuredPath : __DIR__ . '/PlatformCert/' . $merchantId . '/cert.pem';
    }

    protected function fileUri($path)
    {
        return strpos($path, 'file://') === 0 ? $path : 'file://' . $path;
    }

    private function getrequestNew($url, $header = [])
    {
        //curl完成
        $curl = curl_init();
        //设置curl选项
        $header = $header ?: array(
            "content-type: application/json",
            "cache-control: no-cache"
        );

        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_URL, $url);//URL
        curl_setopt($curl, CURLOPT_HEADER, 0);             // 0：不返回头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);//设置超时时间
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // 发出请求
        $response = curl_exec($curl);
        if (false === $response) {
            echo '<br>', curl_error($curl), '<br>';
            return false;
        }
        curl_close($curl);
        $forms = json_decode($response, TRUE);
        return $forms;
    }
}
