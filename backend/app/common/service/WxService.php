<?php

namespace app\common\service;

use cores\utils\HasHttpRequest;
use EasyWeChat\Factory;
use GuzzleHttp\Client;
use think\facade\Config;
use think\facade\Log;

class WxService
{
    use HasHttpRequest;

    const NOTIFY_URL = '/api/pay/callback';
    const WECHAT_INVALID_TOKEN_CODES = [40001, 42001, 40014];
    private function ensureDir($path)
    {
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
        }
    }

    private $app_type = 1;
    public function __construct($app_type=1)
    {
        $this->app_type = $app_type;
    }

    private function getApp()
    {
        if($this->app_type == 1){ //小程序
            $config = [
                'app_id' => Config::get('miniprogram.appid'),
                'secret' => Config::get('miniprogram.appsecret'),
            ];
            return Factory::miniProgram($config);
        }
        if($this->app_type == 2){ //小程序支付
            $config = [
                'app_id' => Config::get('miniprogram.appid'),
                'mch_id' => Config::get('miniprogram.mchid'),
                'key' => Config::get('miniprogram.signkey_v2'),
                'notify_url' => $this->getPayNotifyUrl(),
            ];
            return Factory::payment($config);
        }
        if($this->app_type == 3){ //公众号
            $config = [
                'app_id' => Config::get('miniprogram.account_appid'),
                'secret' => Config::get('miniprogram.account_appsecret'),
                'token' => Config::get('miniprogram.account_token'),
                'aes_key' => Config::get('miniprogram.account_aes_key'),
                'response_type' => 'array',
            ];
            return Factory::officialAccount($config);
        }
        if($this->app_type == 4){ //公众号支付
            $config = [
                'app_id' => Config::get('miniprogram.account_appid'),
                'mch_id' => Config::get('miniprogram.account_mchid'),
                'key' => Config::get('miniprogram.account_key_v2'),
                'notify_url' => $this->getPayNotifyUrl(),
            ];
            return Factory::payment($config);
        }

    }

    public function getPayInfo($order_info)
    {
        $payments = $this->getApp()->order->unify([
            'body' => $order_info['body'],
            'out_trade_no' => $order_info['order_id'],
            'total_fee' => $order_info['pay_price'],
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $order_info['openid'],
        ]);
        if(isset($payments['result_code']) && isset($payments['return_code']) && $payments['result_code'] == 'SUCCESS' && $payments['return_code'] == 'SUCCESS'){
            $json = $this->getApp()->jssdk->bridgeConfig($payments['prepay_id']);
            return json_decode($json, true);
        }else{
            $msg = isset($payments['err_code_des']) ? $payments['err_code_des'] : '';
            $msg = $msg ? $msg : (isset($payments['return_msg']) ? $payments['return_msg'] : '获取支付信息失败');
            throwError($msg);
        }
        return $result;
    }

    public function getAppData()
    {
        return $this->getApp();
    }

    public function getPayCallBackInfo()
    {
        return $this->getApp();
    }

    /**获取用户openid
     * @param $code
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \cores\exception\BaseException
     */
    public function getUserOpenid($code)
    {
//        $result = [
//            'openid' => 'oCzgA7RnL28lGwTP5bK1Nt09_wfU',
//        ];
        $result = $this->getApp()->auth->session($code);
        if(!empty($result['errcode'])){
            throwError($result['errmsg']);
        }
        return $result;
    }

    public function getAccessToken()
    {
        return $this->getStableAccessToken();
    }

    public function getStableAccessToken($forceRefresh = false)
    {
        if ($this->app_type !== 1) {
            $access_token = $this->getApp()->access_token->getToken($forceRefresh);
            return $access_token['access_token'];
        }

        $cacheKey = 'wechat_stable_access_token_' . Config::get('miniprogram.appid');
        if (!$forceRefresh) {
            $cachedToken = cache($cacheKey);
            if ($cachedToken) {
                return $cachedToken;
            }
        }

        $result = $this->postJson('https://api.weixin.qq.com/cgi-bin/stable_token', [
            'grant_type' => 'client_credential',
            'appid' => Config::get('miniprogram.appid'),
            'secret' => Config::get('miniprogram.appsecret'),
            'force_refresh' => (bool)$forceRefresh,
        ]);

        if (!empty($result['access_token'])) {
            $expire = isset($result['expires_in']) ? max(60, (int)$result['expires_in'] - 300) : 6900;
            cache($cacheKey, $result['access_token'], $expire);
            return $result['access_token'];
        }

        Log::error('wechat stable access token failed: ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        throwError('微信凭证获取失败，请稍后重试');
    }

    private function isInvalidAccessTokenResult($result)
    {
        if (empty($result['errcode'])) {
            return false;
        }
        return in_array((int)$result['errcode'], self::WECHAT_INVALID_TOKEN_CODES, true);
    }

    private function requestMiniProgramApi($url, $data, $errorMessage, $forceRefresh = false)
    {
        $result = $this->postJson($url, $data, ['access_token' => $this->getStableAccessToken($forceRefresh)]);
        if (!$forceRefresh && $this->isInvalidAccessTokenResult($result)) {
            Log::warning('wechat access token expired, retrying stable token: ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $this->requestMiniProgramApi($url, $data, $errorMessage, true);
        }
        if (!empty($result['errcode']) && (int)$result['errcode'] !== 0) {
            Log::error('wechat api failed: ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            throwError($errorMessage);
        }
        return $result;
    }

    private function saveMiniProgramBinaryApi($url, $data, $filepath, $filename, $errorMessage, $forceRefresh = false)
    {
        try {
            $response = (new Client(['timeout' => 60.0]))->post($url, [
                'query' => ['access_token' => $this->getStableAccessToken($forceRefresh)],
                'headers' => ['content-type' => 'application/json'],
                'body' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (\Throwable $e) {
            Log::error('wechat binary api request failed: ' . $e->getMessage());
            throwError($errorMessage);
        }

        $contentType = $response->getHeaderLine('Content-type');
        $contents = $response->getBody()->getContents();
        if (false !== stripos($contentType, 'json')) {
            $result = json_decode($contents, true);
            if (!$forceRefresh && $this->isInvalidAccessTokenResult($result)) {
                Log::warning('wechat binary api token expired, retrying stable token: ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return $this->saveMiniProgramBinaryApi($url, $data, $filepath, $filename, $errorMessage, true);
            }
            Log::error('wechat binary api failed: ' . $contents);
            throwError($errorMessage);
        }

        if ($contents === '') {
            Log::error('wechat binary api returned empty body');
            throwError($errorMessage);
        }

        $this->ensureDir($filepath);
        if (file_put_contents($filepath . '/' . $filename, $contents) === false) {
            Log::error('wechat binary api save failed: ' . $filepath . '/' . $filename);
            throwError($errorMessage);
        }
    }

    /**获取用户绑定的手机号码
     * @param $code
     * @return mixed
     * @throws \cores\exception\BaseException
     */
    public function getUserPhone($code)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/getuserphonenumber';
        $data = [
            'code' => $code
        ];
        $result = $this->requestMiniProgramApi($url, $data, '手机号授权失败，请稍后重试');
        return  $result['phone_info']['purePhoneNumber'];
    }

    public function generateShortLink($pageUrl, $pageTitle = '', $isPermanent = false)
    {
        $url = 'https://api.weixin.qq.com/wxa/genwxashortlink';
        $data = [
            'page_url' => $pageUrl,
            'is_permanent' => (bool)$isPermanent,
        ];
        if ($pageTitle !== '') {
            $data['page_title'] = $pageTitle;
        }
        $result = $this->requestMiniProgramApi($url, $data, '获取短链失败，请稍后重试');
        if (empty($result['link'])) {
            throwError('获取短链失败');
        }
        return $result['link'];
    }

    public function generateUrlLink($path, $query = '', $expireInterval = 2592000)
    {
        $url = 'https://api.weixin.qq.com/wxa/generate_urllink';
        $expireTime = time() + max(60, (int)$expireInterval);
        $data = [
            'path' => '/' . ltrim((string)$path, '/'),
            'query' => (string)$query,
            'is_expire' => true,
            'expire_type' => 0,
            'expire_time' => $expireTime,
            'env_version' => 'release',
        ];
        $result = $this->requestMiniProgramApi($url, $data, '获取链接失败，请稍后重试');
        if (empty($result['url_link'])) {
            throwError('获取链接失败');
        }
        return $result['url_link'];
    }

    /**生成小程序二维码
     * @param $data
     * @return void
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     */
    public function getWxQrcode($data)
    {
        return $this->saveMiniProgramBinaryApi('https://api.weixin.qq.com/wxa/getwxacode', [
            'path' => $data['path'],
            'width' => 100,
        ], $data['filepath'], $data['filename'], '生成二维码失败，请稍后重试');
    }

    /**不限制小程序码
     * @param $data
     * @return string|void
     * @throws \cores\exception\BaseException
     */
    public function getUnlimitQrcode($data)
    {
        $options = [
            'scene' => $data['scene'],
            'check_path' => false,
        ];
        if (!empty($data['path'])) {
            $options['page'] = ltrim($data['path'], '/');
        }
        return $this->saveMiniProgramBinaryApi('https://api.weixin.qq.com/wxa/getwxacodeunlimit', $options, $data['filepath'], $data['filename'], '生成二维码失败，请稍后重试');
    }

    /**回调地址
     * @return string
     */
    private function getPayNotifyUrl()
    {
        return 'https://'. $_SERVER['HTTP_HOST'] . self::NOTIFY_URL;
    }
}
